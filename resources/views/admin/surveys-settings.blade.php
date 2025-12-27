@extends('admin.layouts.app')

@section('page-title', 'تنظیمات نظرسنجی')
@section('page-description', 'ویرایش وضعیت، زمان بندی و تنظیمات دسترسی نظرسنجی.')

@section('content')
    <link rel="stylesheet" href="{{ asset('vendor/persian-datepicker/persian-datepicker.min.css') }}">
    <style>
        .settings-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .settings-card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            padding: 1.5rem;
        }
        .settings-header {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-bottom: 1.25rem;
        }
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }
        .settings-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .settings-section h3 {
            margin: 0;
            font-size: 1.1rem;
        }
        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .form-field input,
        .form-field select,
        .form-field textarea {
            border: 1px solid rgba(15, 23, 42, 0.16);
            border-radius: 14px;
            padding: 0.85rem 1rem;
            font-family: inherit;
        }
        .jalali-date-input {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .jalali-date-input input[type="text"] {
            border: 1px solid rgba(15, 23, 42, 0.16);
            border-radius: 14px;
            padding: 0.85rem 1rem;
            font-size: 0.95rem;
            font-family: inherit;
            direction: rtl;
        }
        .toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        .actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }
        .actions button,
        .actions a {
            border: none;
            border-radius: 14px;
            padding: 0.85rem 1.6rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .actions .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .actions .ghost {
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
        }
        .helper-text {
            color: var(--muted);
            font-size: 0.85rem;
        }
        .pwt-datepicker {
            z-index: 1200;
        }
    </style>

    <div class="settings-wrapper">
        <div class="settings-card">
            <div class="settings-header">
                <h2>تنظیمات نظرسنجی: {{ $survey->title }}</h2>
                <p class="helper-text">در این صفحه وضعیت، زمان بندی و دسترسی نظرسنجی را تنظیم کنید.</p>
            </div>

            @if ($errors->updateSurvey->any())
                <div class="helper-text" style="color: #dc2626; margin-bottom: 1rem;">
                    لطفا خطاهای فرم را بررسی کنید.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.surveys.update', $survey) }}" data-jalali-form>
                @csrf
                @method('PUT')

                <div class="settings-section">
                    <h3>زمان بندی و وضعیت</h3>
                    <div class="settings-grid">
                        <label class="form-field">
                            <span>وضعیت</span>
                            <select name="status">
                                @foreach ($statusOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', $survey->status) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status', 'updateSurvey')
                                <small class="helper-text" style="color: #dc2626;">{{ $message }}</small>
                            @enderror
                        </label>
                        <label class="form-field">
                            <span>شروع انتشار</span>
                            <div class="jalali-date-input" data-jalali-input>
                                <input id="start-at" type="text" name="start_at" placeholder="مثلاً 1403/01/12"
                                       value="{{ old('start_at', $survey->start_at ? jalali_date($survey->start_at) : '') }}">
                            </div>
                            @error('start_at', 'updateSurvey')
                                <small class="helper-text" style="color: #dc2626;">{{ $message }}</small>
                            @enderror
                        </label>
                        <label class="form-field">
                            <span>پایان انتشار</span>
                            <div class="jalali-date-input" data-jalali-input>
                                <input id="end-at" type="text" name="end_at" placeholder="مثلاً 1403/02/01"
                                       value="{{ old('end_at', $survey->end_at ? jalali_date($survey->end_at) : '') }}">
                            </div>
                            @error('end_at', 'updateSurvey')
                                <small class="helper-text" style="color: #dc2626;">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>محدودیت پاسخ</h3>
                    <div class="settings-grid">
                        <label class="form-field">
                            <span>بازه زمانی پاسخ (ساعت)</span>
                            <input type="number" name="response_window_hours" min="1" max="720"
                                   value="{{ old('response_window_hours', $survey->response_window_hours) }}">
                            @error('response_window_hours', 'updateSurvey')
                                <small class="helper-text" style="color: #dc2626;">{{ $message }}</small>
                            @enderror
                        </label>
                        <label class="form-field">
                            <span>سقف تعداد پاسخ</span>
                            <input type="number" name="response_limit" min="1"
                                   value="{{ old('response_limit', $survey->response_limit) }}">
                            @error('response_limit', 'updateSurvey')
                                <small class="helper-text" style="color: #dc2626;">{{ $message }}</small>
                            @enderror
                        </label>
                        <label class="form-field">
                            <span>بازه ویرایش پاسخ (ساعت)</span>
                            <input type="number" name="response_edit_window_hours" min="1" max="720"
                                   value="{{ old('response_edit_window_hours', $survey->response_edit_window_hours) }}">
                            @error('response_edit_window_hours', 'updateSurvey')
                                <small class="helper-text" style="color: #dc2626;">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>حریم خصوصی و دسترسی</h3>
                    <div class="settings-grid">
                        <label class="toggle">
                            <input type="hidden" name="require_auth" value="0">
                            <input type="checkbox" name="require_auth" value="1"
                                   @checked(old('require_auth', $survey->require_auth))>
                            فقط کاربران وارد شده بتوانند پاسخ بدهند
                        </label>
                        <label class="toggle">
                            <input type="hidden" name="is_anonymous" value="0">
                            <input type="checkbox" name="is_anonymous" value="1"
                                   @checked(old('is_anonymous', $survey->is_anonymous))>
                            پاسخ ها ناشناس ثبت شوند
                        </label>
                        <label class="toggle">
                            <input type="hidden" name="track_location" value="0">
                            <input type="checkbox" name="track_location" value="1"
                                   @checked(old('track_location', $survey->track_location))>
                            ثبت موقعیت جغرافیایی پاسخ دهنده
                        </label>
                        <label class="toggle">
                            <input type="hidden" name="prevent_multiple_submissions" value="0">
                            <input type="checkbox" name="prevent_multiple_submissions" value="1"
                                   @checked(old('prevent_multiple_submissions', $survey->prevent_multiple_submissions))>
                            جلوگیری از ارسال چندباره
                        </label>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>تجربه کاربر</h3>
                    <div class="settings-grid">
                        <label class="toggle">
                            <input type="hidden" name="allow_edit" value="0">
                            <input type="checkbox" name="allow_edit" value="1"
                                   @checked(old('allow_edit', $survey->allow_edit))>
                            اجازه ویرایش پاسخ
                        </label>
                        <label class="toggle">
                            <input type="hidden" name="allow_partial" value="0">
                            <input type="checkbox" name="allow_partial" value="1"
                                   @checked(old('allow_partial', $survey->allow_partial))>
                            ذخیره پاسخ های ناقص
                        </label>
                        <label class="toggle">
                            <input type="hidden" name="shuffle_questions" value="0">
                            <input type="checkbox" name="shuffle_questions" value="1"
                                   @checked(old('shuffle_questions', $survey->shuffle_questions))>
                            جابجایی ترتیب سوالات
                        </label>
                        <label class="toggle">
                            <input type="hidden" name="shuffle_options" value="0">
                            <input type="checkbox" name="shuffle_options" value="1"
                                   @checked(old('shuffle_options', $survey->shuffle_options))>
                            جابجایی ترتیب گزینه ها
                        </label>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>نمایش نتایج</h3>
                    <div class="settings-grid">
                        <label class="toggle">
                            <input type="hidden" name="show_results_after_submit" value="0">
                            <input type="checkbox" name="show_results_after_submit" value="1"
                                   @checked(old('show_results_after_submit', $survey->show_results_after_submit))>
                            نمایش نتایج بعد از ارسال
                        </label>
                        <label class="form-field">
                            <span>دسترسی نتایج</span>
                            <select name="result_visibility">
                                @foreach ($resultVisibilityOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(old('result_visibility', $survey->result_visibility) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('result_visibility', 'updateSurvey')
                                <small class="helper-text" style="color: #dc2626;">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>اعلان و پیام</h3>
                    <div class="settings-grid">
                        <label class="form-field">
                            <span>ایمیل های دریافت اعلان</span>
                            <input type="text" name="notification_emails"
                                   value="{{ old('notification_emails', implode(', ', $survey->notification_emails ?? [])) }}"
                                   placeholder="example@domain.com, ops@domain.com">
                            <small class="helper-text">چند ایمیل را با کاما جدا کنید.</small>
                            @error('notification_emails', 'updateSurvey')
                                <small class="helper-text" style="color: #dc2626;">{{ $message }}</small>
                            @enderror
                        </label>
                        <label class="form-field">
                            <span>پیام تشکر بعد از ثبت</span>
                            <input type="text" name="thank_you_message"
                                   value="{{ old('thank_you_message', $survey->thank_you_message) }}">
                        </label>
                    </div>
                </div>

                <div class="form-field" style="margin-top: 1.25rem;">
                    <span>گروه های مخاطب</span>
                    <select name="audience_filters[]" multiple size="6">
                        @foreach ($audiencePresets as $preset)
                            <option value="{{ $preset }}" @selected(in_array($preset, old('audience_filters', $survey->audience_filters ?? []), true))>
                                {{ $preset }}
                            </option>
                        @endforeach
                    </select>
                    <small class="helper-text">برای انتخاب چند مورد، کلید Ctrl را نگه دارید.</small>
                    @error('audience_filters', 'updateSurvey')
                        <small class="helper-text" style="color: #dc2626;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="actions">
                    <button type="submit" class="primary">ذخیره تنظیمات</button>
                    <a href="{{ route('admin.surveys.index') }}" class="ghost">بازگشت</a>
                </div>
            </form>
        </div>
    </div>
    <script src="{{ asset('vendor/persian-datepicker/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/persian-datepicker/persian-date.min.js') }}"></script>
    <script src="{{ asset('vendor/persian-datepicker/persian-datepicker.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.jQuery || !jQuery().pDatepicker) {
                return;
            }

            jQuery('#start-at, #end-at').pDatepicker({
                format: 'YYYY/MM/DD',
                initialValue: false,
                autoClose: true,
                calendarType: 'persian',
                initialValueType: 'persian',
                toolbox: {
                    calendarSwitch: false
                }
            });
        });
    </script>
@endsection
