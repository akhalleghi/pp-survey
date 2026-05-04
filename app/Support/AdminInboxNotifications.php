<?php

namespace App\Support;

use App\Models\AdminUser;
use App\Models\Survey;
use Carbon\Carbon;

/**
 * اعلان‌های سرصفحهٔ پنل بر اساس دادهٔ لحظه‌ای (بدون جدول جداگانه).
 *
 * @phpstan-type NotifyItem array{
 *   key: string,
 *   title: string,
 *   body: string,
 *   href: string|null,
 *   at: \Carbon\Carbon|null,
 *   tone: 'info'|'warning'|'danger'
 * }
 */
final class AdminInboxNotifications
{
    /**
     * @return list<NotifyItem>
     */
    public static function collect(?AdminUser $admin): array
    {
        if (! $admin) {
            return [];
        }

        $items = [];
        if ($admin->isAdmin()) {
            $items = array_merge($items, self::forMainAdmin());
        } elseif ($admin->isSupervisor() && $admin->hasPermission(AdminPermissions::SURVEYS)) {
            $items = array_merge($items, self::forSupervisor($admin));
        }

        usort(
            $items,
            static function (array $a, array $b): int {
                $ta = $a['at'] instanceof Carbon ? $a['at']->getTimestamp() : 0;
                $tb = $b['at'] instanceof Carbon ? $b['at']->getTimestamp() : 0;

                return $tb <=> $ta;
            }
        );

        return array_slice($items, 0, 20);
    }

    public static function count(?AdminUser $admin): int
    {
        return count(self::collect($admin));
    }

    /**
     * @return list<NotifyItem>
     */
    private static function forMainAdmin(): array
    {
        $surveys = Survey::query()
            ->where('status', 'pending_approval')
            ->with(['unit:id,name'])
            ->latest('updated_at')
            ->limit(18)
            ->get();

        return $surveys->map(static function (Survey $survey): array {
            $unitLabel = $survey->unit ? $survey->unit->name : 'بدون واحد';

            return [
                'key' => 'pending-approval-'.$survey->id,
                'title' => 'درخواست تأیید انتشار',
                'body' => 'نظرسنجی «'.$survey->title.'» ('.$unitLabel.') در انتظار بررسی شماست.',
                'href' => route('admin.surveys.edit', $survey),
                'at' => $survey->updated_at,
                'tone' => 'warning',
            ];
        })->all();
    }

    /**
     * @return list<NotifyItem>
     */
    private static function forSupervisor(AdminUser $admin): array
    {
        $uid = $admin->id;
        $pending = Survey::query()
            ->where('created_by_admin_user_id', $uid)
            ->where('status', 'pending_approval')
            ->with(['unit:id,name'])
            ->latest('updated_at')
            ->limit(12)
            ->get();

        $rejected = Survey::query()
            ->where('created_by_admin_user_id', $uid)
            ->where('status', 'draft')
            ->whereNotNull('publish_rejection_reason')
            ->where('publish_rejection_reason', '!=', '')
            ->with(['unit:id,name'])
            ->latest('updated_at')
            ->limit(12)
            ->get();

        $out = [];

        foreach ($pending as $survey) {
            $unitLabel = $survey->unit ? $survey->unit->name : 'بدون واحد';
            $out[] = [
                'key' => 'sup-pending-'.$survey->id,
                'title' => 'در انتظار تأیید مدیر',
                'body' => 'درخواست انتشار نظرسنجی «'.$survey->title.'» ('.$unitLabel.') هنوز بررسی نشده است.',
                'href' => route('admin.surveys.edit', $survey),
                'at' => $survey->updated_at,
                'tone' => 'info',
            ];
        }

        foreach ($rejected as $survey) {
            $reason = mb_strlen($survey->publish_rejection_reason) > 140
                ? mb_substr($survey->publish_rejection_reason, 0, 140).'…'
                : $survey->publish_rejection_reason;
            $out[] = [
                'key' => 'sup-rejected-'.$survey->id,
                'title' => 'رد درخواست انتشار',
                'body' => 'برای «'.$survey->title.'»: '.$reason,
                'href' => route('admin.surveys.edit', $survey),
                'at' => $survey->updated_at,
                'tone' => 'danger',
            ];
        }

        return $out;
    }
}
