<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\Survey;
use App\Models\Unit;
use App\Services\Sms\SmsCampaignService;
use App\Services\Sms\SmsMessageComposer;
use App\Services\Sms\SmsPanelService;
use App\Support\PersianCalendar;
use App\Support\SmsTargetingMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SmsManagementController extends Controller
{
    public function __construct(
        private readonly SmsCampaignService $campaignService,
        private readonly SmsPanelService $smsPanelService,
    ) {}

    public function index(Request $request): View
    {
        $validator = validator($request->all(), [
            'log_mobile' => ['nullable', 'string', 'max:20'],
            'log_status' => ['nullable', 'string', Rule::in([SmsMessage::STATUS_SENT, SmsMessage::STATUS_FAILED, SmsMessage::STATUS_PENDING])],
            'log_from' => ['nullable', 'string', 'max:32'],
            'log_to' => ['nullable', 'string', 'max:32'],
        ]);

        $validator->after(function ($v) use ($request): void {
            if ($request->filled('log_from') && PersianCalendar::parseDateStart($request->input('log_from')) === null) {
                $v->errors()->add('log_from', 'تاریخ «از» معتبر نیست.');
            }
            if ($request->filled('log_to') && PersianCalendar::parseDateEnd($request->input('log_to')) === null) {
                $v->errors()->add('log_to', 'تاریخ «تا» معتبر نیست.');
            }
        });

        $validator->validate();

        $logsQuery = SmsMessage::query()
            ->with(['campaign.survey:id,title', 'campaign.adminUser:id,username', 'provider:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('log_mobile')) {
            $term = preg_replace('/\D+/', '', $request->string('log_mobile')->toString()) ?? '';
            if ($term !== '') {
                $logsQuery->where('recipient_mobile', 'like', '%'.ltrim($term, '0').'%');
            }
        }

        if ($request->filled('log_status')) {
            $logsQuery->where('status', $request->string('log_status')->toString());
        }

        $from = PersianCalendar::parseDateStart($request->input('log_from'));
        if ($from) {
            $logsQuery->where('created_at', '>=', $from);
        }

        $to = PersianCalendar::parseDateEnd($request->input('log_to'));
        if ($to) {
            $logsQuery->where('created_at', '<=', $to);
        }

        $logs = $logsQuery->paginate(25, ['*'], 'log_page')->withQueryString();

        $surveys = Survey::query()
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'public_token', 'status']);

        $activeProvider = $this->smsPanelService->activeProvider()?->load('config');

        $personnelOptions = Personnel::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(500)
            ->get(['id', 'first_name', 'last_name', 'personnel_code']);

        return view('admin.sms.index', [
            'logs' => $logs,
            'surveys' => $surveys,
            'personnelOptions' => $personnelOptions,
            'units' => Unit::query()->orderBy('name')->get(['id', 'name']),
            'positions' => Position::query()->orderBy('name')->get(['id', 'name']),
            'genderOptions' => Personnel::GENDERS,
            'targetingModes' => SmsTargetingMode::labels(),
            'targetingIcons' => SmsTargetingMode::icons(),
            'audiencePresets' => [
                'unit' => 'واحد سازمانی',
                'gender' => 'جنسیت',
                'position' => 'سمت',
                'personnel' => 'افراد مشخص',
            ],
            'activeProvider' => $activeProvider,
            'activeTab' => $request->query('tab', 'send'),
        ]);
    }

    public function surveyTemplate(Survey $survey): JsonResponse
    {
        if (! $survey->public_token) {
            return response()->json([
                'ok' => false,
                'message' => 'ابتدا برای این نظرسنجی لینک عمومی ایجاد کنید.',
            ], 422);
        }

        $url = route('surveys.public.show', $survey->public_token);

        $audienceConfig = \App\Support\SurveyAudience::normalize($survey->audience_filters ?? []);

        return response()->json([
            'ok' => true,
            'template' => SmsMessageComposer::defaultSurveyTemplate($survey, $url),
            'public_url' => $url,
            'audience_summary' => \App\Support\SurveyAudience::describeFilters($audienceConfig),
            'survey_has_filters' => \App\Support\SurveyAudience::hasActiveFilters($audienceConfig),
        ]);
    }

    public function personnelSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $query = Personnel::query()
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(40);

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('personnel_code', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%")
                    ->orWhere('national_code', 'like', "%{$q}%");
            });
        }

        $items = $query->get(['id', 'first_name', 'last_name', 'personnel_code', 'mobile'])->map(fn (Personnel $p) => [
            'id' => $p->id,
            'name' => trim($p->first_name.' '.$p->last_name),
            'personnel_code' => $p->personnel_code,
            'mobile' => $p->mobile,
        ]);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function preview(Request $request): JsonResponse
    {
        $payload = $this->validatedCampaignPayload($request);

        try {
            $data = $this->campaignService->preview($payload);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['ok' => true, 'data' => $data]);
    }

    public function storeDraft(Request $request): JsonResponse
    {
        $admin = current_admin();
        if (! $admin) {
            abort(403);
        }

        $payload = $this->validatedCampaignPayload($request);
        $payload['recipients_checksum'] = $request->string('recipients_checksum')->toString();

        try {
            $campaign = $this->campaignService->createDraftCampaign($admin, $payload);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'campaign' => [
                'id' => $campaign->id,
                'recipient_count' => $campaign->recipient_count,
                'confirm_phrase' => $campaign->confirm_phrase,
                'survey_title' => $campaign->survey?->title,
                'targeting_mode' => $campaign->targeting_mode,
            ],
        ]);
    }

    public function confirmSend(Request $request, SmsCampaign $campaign): JsonResponse
    {
        $admin = current_admin();
        if (! $admin || $campaign->admin_user_id !== $admin->id) {
            abort(403);
        }

        $validated = $request->validate([
            'confirm_phrase' => ['required', 'string', 'max:64'],
            'admin_password' => ['required', 'string', 'max:128'],
            'acknowledged' => ['accepted'],
        ], [
            'confirm_phrase.required' => 'عبارت تأیید را وارد کنید.',
            'admin_password.required' => 'رمز عبور مدیر الزامی است.',
            'acknowledged.accepted' => 'تأیید مطالعهٔ فهرست گیرندگان الزامی است.',
        ]);

        try {
            $this->campaignService->confirmCampaignForSending(
                $campaign,
                $admin,
                $validated['confirm_phrase'],
                $validated['admin_password'],
                $request->boolean('acknowledged')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        $campaign->refresh();

        return response()->json([
            'ok' => true,
            'message' => 'تأیید انجام شد. ارسال پیامک‌ها آغاز می‌شود.',
            'campaign_id' => $campaign->id,
            'total' => (int) $campaign->recipient_count,
        ]);
    }

    public function sendStep(SmsCampaign $campaign): JsonResponse
    {
        $admin = current_admin();
        if (! $admin || $campaign->admin_user_id !== $admin->id) {
            abort(403);
        }

        if (! in_array($campaign->status, [SmsCampaign::STATUS_PROCESSING, SmsCampaign::STATUS_QUEUED], true)) {
            return response()->json([
                'ok' => false,
                'message' => 'این کمپین در وضعیت ارسال نیست.',
            ], 422);
        }

        $data = app(\App\Services\Sms\SmsCampaignProcessor::class)->sendNext($campaign->id);

        return response()->json([
            'ok' => true,
            'data' => $data,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCampaignPayload(Request $request): array
    {
        $rules = [
            'survey_id' => ['nullable', 'integer', 'exists:surveys,id'],
            'targeting_mode' => ['required', 'string', Rule::in(SmsTargetingMode::all())],
            'message' => ['required', 'string', 'min:10', 'max:900'],
            'audience_modes' => ['nullable', 'array'],
            'audience_modes.*' => [Rule::in(['unit', 'gender', 'position', 'personnel'])],
            'audience_unit_ids' => ['nullable', 'array'],
            'audience_unit_ids.*' => ['integer', 'exists:units,id'],
            'audience_genders' => ['nullable', 'array'],
            'audience_genders.*' => [Rule::in(array_keys(Personnel::GENDERS))],
            'audience_position_ids' => ['nullable', 'array'],
            'audience_position_ids.*' => ['integer', 'exists:positions,id'],
            'audience_personnel_ids' => ['nullable', 'array'],
            'audience_personnel_ids.*' => ['integer', 'exists:personnel,id'],
            'personnel_ids' => ['nullable', 'array'],
            'personnel_ids.*' => ['integer', 'exists:personnel,id'],
            'free_numbers' => ['nullable', 'string', 'max:20000'],
        ];

        $validated = $request->validate($rules, [
            'targeting_mode.required' => 'نحوه ارسال را انتخاب کنید.',
            'message.required' => 'متن پیامک الزامی است.',
        ]);

        if (($validated['targeting_mode'] ?? '') === SmsTargetingMode::SURVEY_ELIGIBLE && empty($validated['survey_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'survey_id' => 'برای ارسال به مخاطبان مجاز نظرسنجی، انتخاب نظرسنجی الزامی است.',
            ]);
        }

        return $validated;
    }
}
