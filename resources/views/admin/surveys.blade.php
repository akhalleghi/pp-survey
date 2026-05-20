@extends('admin.layouts.app')

@section('page-title', 'مدیریت نظرسنجی‌ها')
@section('page-description', 'ساخت نظرسنجی جدید، پایش وضعیت و اعمال تنظیمات محدودیت پاسخ و دسترسی کاربران.')

@php
    $audiencePresets = $audiencePresets ?? ['همه کاربران', 'براساس واحد', 'براساس جنسیت', 'براساس سمت', 'براساس مدرک تحصیلی', 'انتخابی توسط ادمین'];
    $metrics = $metrics ?? ['active' => 0, 'pending_approval' => 0, 'responses' => 0, 'avg_questions' => 0, 'closed' => 0];
    $statusLabels = [
        'active' => 'فعال',
        'draft' => 'در حال آماده سازی',
        'pending_approval' => 'در انتظار تأیید مدیر',
        'closed' => 'بسته شده',
    ];
    $statusFilters = [
        '' => 'همه',
        'active' => 'فعال',
        'draft' => 'در حال آماده سازی',
        'pending_approval' => 'در انتظار تأیید',
        'closed' => 'بسته شده',
    ];
    $statusFilterIcons = [
        '' => 'fa-layer-group',
        'active' => 'fa-circle-check',
        'draft' => 'fa-pen-ruler',
        'pending_approval' => 'fa-hourglass-half',
        'closed' => 'fa-lock',
    ];
    $statusBadgeIcons = [
        'active' => 'fa-circle-check',
        'draft' => 'fa-pen-ruler',
        'pending_approval' => 'fa-hourglass-half',
        'closed' => 'fa-lock',
    ];
    $admin = $admin ?? current_admin();
    $units = $units ?? collect();
@endphp

@section('content')
    <style>
        :root {
            --surface: #fff;
            --border: rgba(15, 23, 42, 0.08);
        }
        .surveys-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .status-message {
            background: rgba(46, 213, 115, 0.15);
            border: 1px solid rgba(46, 213, 115, 0.4);
            color: #0d8a4d;
            padding: 0.85rem 1.1rem;
            border-radius: 16px;
            font-weight: 600;
        }
        .status-message.status-message--error {
            background: rgba(220, 38, 38, 0.1);
            border-color: rgba(220, 38, 38, 0.35);
            color: #991b1b;
        }
        .surveys-hero {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: clamp(1.2rem, 4vw, 2.2rem);
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1.25rem;
            align-items: center;
        }
        .surveys-hero h2 {
            margin: 0;
            font-size: clamp(1.2rem, 3vw, 1.85rem);
        }
        .surveys-hero p {
            margin: 0.5rem 0 0;
            color: var(--muted);
            line-height: 1.8;
        }
        .hero-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .hero-actions button {
            border: none;
            border-radius: 18px;
            padding: 0.9rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
        }
        .hero-actions .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .hero-actions .ghost {
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .survey-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.9rem;
        }
        .survey-stat-card {
            background: var(--surface);
            border-radius: 22px;
            border: 1px solid var(--border);
            padding: 1.2rem;
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            position: relative;
            overflow: hidden;
        }
        .survey-stat-card-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 14px;
            background: rgba(214, 17, 25, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }
        .survey-stat-card-icon i {
            font-size: 1.15rem;
        }
        .survey-stat-card-body {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            position: relative;
            z-index: 1;
            min-width: 0;
        }
        .survey-stat-card::after {
            content: '';
            position: absolute;
            inset: auto auto -40% -30%;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(214, 17, 25, 0.15), transparent 65%);
        }
        .survey-stat-card span {
            color: var(--muted);
            font-size: 0.85rem;
        }
        .survey-stat-card strong {
            font-size: clamp(1.4rem, 2vw, 1.9rem);
        }
        .survey-table-card {
            background: var(--surface);
            border-radius: 28px;
            border: 1px solid var(--border);
            padding: clamp(1rem, 3vw, 1.8rem);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .survey-table-wrapper {
            /* overflow visible تا منوی کشویی اقدامات از سلول/جدول بیرون برود.
               در عرض‌های کوچک‌تر، اسکرول افقی فعال می‌شود (در media query پایین). */
            overflow: visible;
            -webkit-overflow-scrolling: touch;
            margin-inline: -0.15rem;
            padding-bottom: 0.25rem;
        }
        /* ستون اقدامات: منو بالاتر از ردیف‌های بعدی/قبلی (جدول + border-collapse) */
        .surveys-table td:last-child {
            overflow: visible;
        }
        .surveys-table tbody tr {
            position: relative;
            z-index: 0;
        }
        .surveys-table tbody tr:has(.survey-actions-dropdown:hover),
        .surveys-table tbody tr:has(.survey-actions-dropdown:focus-within) {
            z-index: 1200;
            isolation: isolate;
            background: var(--surface);
        }
        .surveys-table tbody td:last-child {
            position: relative;
            z-index: 0;
        }
        .surveys-table tbody tr:has(.survey-actions-dropdown:hover) td:last-child,
        .surveys-table tbody tr:has(.survey-actions-dropdown:focus-within) td:last-child {
            z-index: 2;
        }
        .surveys-table th:last-child,
        .surveys-table td:last-child {
            width: 1%;
            min-width: 7.5rem;
            max-width: 10rem;
            vertical-align: middle;
        }
        .table-head {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
        }
        .survey-search {
            flex: 1;
            min-width: 220px;
            position: relative;
        }
        .survey-search input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 0.85rem 1rem;
            padding-right: 2.6rem;
            font-family: inherit;
        }
        .survey-search .survey-search-icon {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            font-size: 1rem;
            color: var(--muted);
            pointer-events: none;
        }
        .surveys-hero-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }
        .surveys-hero-title i {
            color: var(--primary);
            font-size: 1.35rem;
        }
        .filter-chip i {
            font-size: 0.8rem;
            opacity: 0.85;
        }
        .surveys-table th .th-label {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
        }
        .surveys-table th .th-label i {
            color: var(--primary);
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .survey-empty-state i {
            display: block;
            font-size: 2rem;
            color: var(--muted);
            margin-bottom: 0.65rem;
            opacity: 0.65;
        }
        .survey-filters {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }
        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 0.4rem 0.85rem;
            font-size: 0.85rem;
            cursor: pointer;
            background: rgba(15, 23, 42, 0.02);
            text-decoration: none;
            color: inherit;
        }
        .filter-chip.active {
            background: rgba(214, 17, 25, 0.12);
            border-color: rgba(214, 17, 25, 0.5);
            color: var(--primary);
        }
        .surveys-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .surveys-table thead {
            background: rgba(15, 23, 42, 0.03);
            color: var(--muted);
        }
        .surveys-table th,
        .surveys-table td {
            padding: 1rem 0.75rem;
            text-align: right;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }
        .survey-name {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .survey-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
        }
        .survey-tag {
            padding: 0.2rem 0.7rem;
            background: rgba(15, 23, 42, 0.06);
            border-radius: 999px;
            font-size: 0.75rem;
            color: var(--muted);
        }
        .survey-tag.muted {
            opacity: 0.7;
        }
        .survey-status {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.35rem 1rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .survey-status.active {
            background: rgba(34, 197, 94, 0.15);
            color: #15803d;
        }
        .survey-status.draft {
            background: rgba(234, 179, 8, 0.22);
            color: #a16207;
        }
        .survey-status.closed {
            background: rgba(15, 23, 42, 0.12);
            color: var(--muted);
        }
        .survey-status.pending_approval {
            background: rgba(59, 130, 246, 0.18);
            color: #1d4ed8;
        }
        .publish-requester {
            display: flex;
            flex-direction: column;
            gap: 0.12rem;
            line-height: 1.25;
        }
        .publish-requester-name {
            font-weight: 600;
            font-size: 0.88rem;
        }
        .publish-requester-user {
            font-size: 0.75rem;
        }
        .reject-publish-lead {
            margin: 0 0 1rem;
            font-size: 0.88rem;
            color: var(--muted);
        }
        .reject-publish-lead strong {
            color: var(--slate);
        }
        .modal-actions .reject-submit {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff;
        }
        .modal-actions .reject-submit:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }
        .survey-actions-dropdown {
            position: relative;
            display: block;
            width: 100%;
            z-index: 0;
        }
        .survey-actions-dropdown:hover,
        .survey-actions-dropdown:focus-within {
            z-index: 5000;
        }
        /* پل نامرئی برای حفظ هاور بین دکمه و منو (منو بالای دکمه باز می‌شود) */
        .survey-actions-dropdown::before {
            content: '';
            position: absolute;
            inset-inline: 0;
            bottom: 100%;
            height: 14px;
            z-index: 18;
        }
        .survey-actions-trigger {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 14px;
            padding: 0.5rem 0.65rem;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            color: var(--slate);
            background: rgba(15, 23, 42, 0.06);
            transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .survey-actions-trigger:hover,
        .survey-actions-dropdown:focus-within .survey-actions-trigger {
            background: rgba(15, 23, 42, 0.1);
            border-color: rgba(214, 17, 25, 0.35);
            box-shadow: 0 0 0 1px rgba(214, 17, 25, 0.12);
        }
        .survey-actions-chevron {
            font-size: 0.7rem;
            flex-shrink: 0;
            transition: transform 0.2s ease;
            opacity: 0.75;
        }
        .modal-actions button {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .survey-actions-dropdown:hover .survey-actions-chevron,
        .survey-actions-dropdown:focus-within .survey-actions-chevron {
            transform: rotate(180deg);
        }
        .survey-actions-menu {
            position: absolute;
            inset-inline-end: 0;
            bottom: calc(100% + 6px);
            top: auto;
            min-width: 12.25rem;
            width: max-content;
            max-width: min(20rem, 88vw);
            padding: 0.35rem;
            margin: 0;
            list-style: none;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            z-index: 40;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.18s ease, visibility 0.18s ease;
        }
        .survey-actions-dropdown:hover .survey-actions-menu,
        .survey-actions-dropdown:focus-within .survey-actions-menu {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .survey-actions-form {
            display: block;
            margin: 0;
        }
        .survey-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
            flex-wrap: wrap;
            margin: 0.15rem 0.25rem 0.1rem;
            padding: 0.45rem 0.55rem;
            font-size: 0.72rem;
            color: var(--muted);
            line-height: 1.4;
            border-top: 1px solid rgba(15, 23, 42, 0.08);
        }
        .survey-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            white-space: nowrap;
        }
        .survey-link-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
            width: 100%;
        }
        .survey-link-url {
            display: block;
            width: 100%;
            margin-top: 0.3rem;
            font-size: 0.65rem;
            color: var(--slate);
            line-height: 1.45;
            word-break: break-all;
            user-select: all;
        }
        .survey-actions-menu-item {
            display: block;
            width: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
        }
        .survey-actions-menu-item + .survey-actions-menu-item {
            margin-top: 2px;
        }
        .survey-actions-menu button,
        .survey-actions-menu a,
        .survey-actions-menu .survey-actions-hint {
            border: none;
            border-radius: 12px;
            padding: 0.55rem 0.75rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.8rem;
            line-height: 1.35;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.5rem;
            width: 100%;
            box-sizing: border-box;
            text-align: right;
            font-family: inherit;
            color: var(--slate);
            background: transparent;
            transition: background 0.12s ease, color 0.12s ease;
        }
        .survey-actions-menu .menu-icon {
            width: 1.15rem;
            text-align: center;
            flex-shrink: 0;
            opacity: 0.88;
        }
        .survey-actions-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .survey-actions-trigger .menu-icon {
            font-size: 0.9rem;
            opacity: 0.75;
        }
        .survey-link-row span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .modal-header h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .modal-header h3 i {
            color: var(--primary);
        }
        .guide-steps li {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        .guide-steps li > i {
            color: var(--primary);
            margin-top: 0.2rem;
            flex-shrink: 0;
        }
        .survey-actions-menu a {
            color: var(--slate);
        }
        .survey-actions-menu button:hover,
        .survey-actions-menu a:hover {
            background: rgba(15, 23, 42, 0.06);
            color: var(--primary);
        }
        .survey-actions-menu .is-muted {
            color: var(--muted);
            font-weight: 500;
            cursor: default;
        }
        .survey-actions-menu .is-muted:hover {
            background: transparent;
            color: var(--muted);
        }
        .survey-actions-menu .is-danger {
            color: #b91c1c;
        }
        .survey-actions-menu .is-danger:hover {
            background: rgba(220, 38, 38, 0.1);
            color: #991b1b;
        }
        .survey-actions-menu .is-success {
            color: #15803d;
            font-weight: 600;
        }
        .survey-actions-menu .is-success:hover {
            background: rgba(34, 197, 94, 0.12);
            color: #166534;
        }
        .survey-actions-hint {
            display: block;
            padding: 0.65rem 1rem;
            font-size: 0.82rem;
            color: var(--muted);
            font-weight: 600;
        }
        .mobile-card {
            display: none;
        }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 20000;
        }
        .modal.open {
            display: flex;
        }
        .modal-dialog {
            width: min(640px, 100%);
            background: var(--surface);
            border-radius: 30px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 40px 80px rgba(15, 23, 42, 0.25);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            max-height: 90vh;
            overflow-y: auto;
        }
        .guide-modal-dialog {
            width: min(760px, 100%);
        }
        .guide-steps {
            margin: 0;
            padding-right: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .guide-steps li {
            line-height: 1.9;
            color: var(--slate);
        }
        .guide-steps li strong {
            color: var(--primary-dark);
        }
        .guide-note {
            margin-top: 0.9rem;
            border: 1px solid rgba(15, 23, 42, 0.1);
            background: rgba(15, 23, 42, 0.03);
            border-radius: 14px;
            padding: 0.85rem 1rem;
            line-height: 1.85;
            color: var(--muted);
            font-size: 0.88rem;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
        }
        .modal-close {
            border: none;
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
        }
        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .form-field input,
        .form-field select,
        .form-field textarea {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 0.85rem 1rem;
            font-family: inherit;
        }
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.8rem;
        }
        .settings-card {
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 0.9rem;
            background: rgba(15, 23, 42, 0.02);
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 600;
        }
        .audience-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }
        .audience-chip {
            background: rgba(214, 17, 25, 0.15);
            color: var(--primary);
            border-radius: 999px;
            padding: 0.3rem 0.85rem;
            font-size: 0.8rem;
        }
        .modal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }
        .modal-actions button {
            border: none;
            border-radius: 18px;
            padding: 0.9rem 1.6rem;
            font-weight: 600;
            cursor: pointer;
        }
        .modal-actions .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .modal-actions .ghost {
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
        }
        .error-text {
            color: #dc2626;
            font-size: 0.85rem;
        }
        @media (max-width: 960px) {
            .surveys-hero {
                grid-template-columns: 1fr;
            }
            .hero-actions {
                justify-content: flex-start;
            }
        }
        @media (max-width: 768px) {
            .survey-table-wrapper {
                overflow-x: auto;
            }
            .table-head {
                flex-direction: column;
                align-items: stretch;
            }
            .surveys-table thead {
                display: none;
            }
            .surveys-table,
            .surveys-table tbody,
            .surveys-table tr,
            .surveys-table td {
                display: block;
                width: 100%;
            }
            .surveys-table tr {
                margin-bottom: 1rem;
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 18px;
                overflow: visible;
            }
            .surveys-table td {
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            }
            .surveys-table td:last-child {
                border-bottom: none;
            }
            .surveys-table th:last-child,
            .surveys-table td:last-child {
                min-width: 0;
                max-width: none;
                width: 100%;
            }
        }
        @media (max-width: 520px) {
            .surveys-hero,
            .survey-table-card,
            .modal-dialog {
                border-radius: 18px;
                padding: 1rem;
            }
        }
    </style>

    <div class="surveys-wrapper">
        @if (session('status'))
            <div class="status-message">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="status-message status-message--error">
                {{ session('error') }}
            </div>
        @endif

        <section class="surveys-hero">
            <div>
                <h2 class="surveys-hero-title">
                    <i class="fa-solid fa-square-poll-vertical" aria-hidden="true"></i>
                    کنترل پنل نظرسنجی‌ها
                </h2>
                <p>از همین صفحه می‌توانید نظرسنجی جدید بسازید، وضعیت انتشار را مدیریت کنید و محدودیت پاسخ یا دسترسی مخاطبان
                    را روی هر نظرسنجی اعمال کنید.</p>
            </div>
            <div class="hero-actions">
                <button type="button" class="primary" id="openAddSurvey">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    افزودن نظرسنجی
                </button>
                <button type="button" class="outline" id="openSurveyGuide">
                    <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                    راهنمای ساخت
                </button>
            </div>
        </section>

        <section class="survey-stats">
            <div class="survey-stat-card">
                <div class="survey-stat-card-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></div>
                <div class="survey-stat-card-body">
                    <span>نظرسنجی‌های فعال</span>
                    <strong>{{ number_format($metrics['active']) }}</strong>
                    <small>در حال دریافت پاسخ</small>
                </div>
            </div>
            <div class="survey-stat-card">
                <div class="survey-stat-card-icon" aria-hidden="true"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="survey-stat-card-body">
                    <span>در انتظار تأیید مدیر</span>
                    <strong>{{ number_format($metrics['pending_approval'] ?? 0) }}</strong>
                    <small>قبل از انتشار رسمی</small>
                </div>
            </div>
            <div class="survey-stat-card">
                <div class="survey-stat-card-icon" aria-hidden="true"><i class="fa-solid fa-reply"></i></div>
                <div class="survey-stat-card-body">
                    <span>پاسخ‌های ثبت‌شده</span>
                    <strong>{{ number_format($metrics['responses']) }}</strong>
                    <small>مجموع همه نظرسنجی‌ها</small>
                </div>
            </div>
            <div class="survey-stat-card">
                <div class="survey-stat-card-icon" aria-hidden="true"><i class="fa-solid fa-list-ol"></i></div>
                <div class="survey-stat-card-body">
                    <span>میانگین تعداد سوال</span>
                    <strong>{{ number_format($metrics['avg_questions'], 1) }}</strong>
                    <small>به‌ازای هر نظرسنجی</small>
                </div>
            </div>
            <div class="survey-stat-card">
                <div class="survey-stat-card-icon" aria-hidden="true"><i class="fa-solid fa-box-archive"></i></div>
                <div class="survey-stat-card-body">
                    <span>نظرسنجی‌های بسته شده</span>
                    <strong>{{ number_format($metrics['closed']) }}</strong>
                    <small>آماده آرشیو یا خروجی</small>
                </div>
            </div>
        </section>

        <section class="survey-table-card">
            <div class="table-head">
                <form class="survey-search" method="GET" action="{{ route('admin.surveys.index') }}">
                    <input type="text" name="search" value="{{ $search }}" placeholder="جستجوی نام یا واحد">
                    @if ($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif
                    <i class="fa-solid fa-magnifying-glass survey-search-icon" aria-hidden="true"></i>
                </form>
                <div class="survey-filters">
                    @foreach ($statusFilters as $key => $label)
                        @php
                            $query = array_filter([
                                'search' => $search,
                                'status' => $key ?: null,
                            ], fn($value) => filled($value));
                            $isActiveFilter = ($key === '' && !$statusFilter) || ($statusFilter === $key);
                        @endphp
                        <a href="{{ route('admin.surveys.index', $query) }}"
                            class="filter-chip {{ $isActiveFilter ? 'active' : '' }}">
                            <i class="fa-solid {{ $statusFilterIcons[$key] ?? 'fa-filter' }}" aria-hidden="true"></i>
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="survey-table-wrapper">
                <table class="surveys-table">
                    <thead>
                        <tr>
                            <th><span class="th-label"><i class="fa-solid fa-file-lines" aria-hidden="true"></i>نام نظرسنجی</span></th>
                            <th><span class="th-label"><i class="fa-solid fa-building" aria-hidden="true"></i>واحد مربوطه</span></th>
                            <th><span class="th-label"><i class="fa-solid fa-list-ol" aria-hidden="true"></i>تعداد سوالات</span></th>
                            <th><span class="th-label"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i>زمان ایجاد</span></th>
                            <th><span class="th-label"><i class="fa-solid fa-chart-column" aria-hidden="true"></i>تعداد پاسخ</span></th>
                            <th><span class="th-label"><i class="fa-solid fa-signal" aria-hidden="true"></i>وضعیت</span></th>
                            <th><span class="th-label"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>درخواست انتشار</span></th>
                            <th><span class="th-label"><i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>اقدامات</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($surveys as $survey)
                            @php
                                $tagList = $survey->tags ?? [];
                                $unitLabel = $survey->unit?->name ?? 'Unknown Unit';
                                $audienceFilters = $survey->audience_filters ?? [];
                                $creator = $survey->creator;
                                $ownerNeedsManagerApproval = $creator
                                    && $creator->isSupervisor()
                                    && $creator->requires_survey_publish_approval
                                    && (int) $survey->created_by_admin_user_id === (int) $creator->id;
                                $isOwnerSupervisor = $admin instanceof \App\Models\AdminUser
                                    && $admin->isSupervisor()
                                    && (int) $survey->created_by_admin_user_id === (int) $admin->id;
                            @endphp
                            <tr>
                                <td>
                                    <div class="survey-name">
                                        <strong>{{ $survey->title }}</strong>
                                        <div class="survey-tags">
                                            @forelse ($tagList as $tag)
                                                <span class="survey-tag">{{ $tag }}</span>
                                            @empty
                                                <span class="survey-tag muted">بدون برچسب</span>
                                            @endforelse
                                        </div>
                                        @include('admin.partials.survey-publish-rejection-notice', ['survey' => $survey])
                                    </div>
                                </td>
                                <td>{{ $unitLabel }}</td>
                                <td>{{ $survey->questions_count }}</td>
                                <td>{{ $survey->created_at ? jalali_date($survey->created_at, 'Y/m/d H:i') : '-' }}</td>
                                <td>{{ number_format($survey->responses_count) }}</td>
                                <td>
                                    <span class="survey-status {{ $survey->status }}">
                                        <i class="fa-solid {{ $statusBadgeIcons[$survey->status] ?? 'fa-circle-question' }}" aria-hidden="true"></i>
                                        {{ $statusLabels[$survey->status] ?? 'نامشخص' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($survey->publishRequestedBy)
                                        <div class="publish-requester">
                                            <span
                                                class="publish-requester-name">{{ $survey->publishRequestedBy->name ?: $survey->publishRequestedBy->username }}</span>
                                            @if ($survey->publishRequestedBy->name && filled($survey->publishRequestedBy->username))
                                                <small
                                                    class="publish-requester-user muted">{{ $survey->publishRequestedBy->username }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="survey-actions-dropdown">
                                        <button type="button" class="survey-actions-trigger" aria-haspopup="menu"
                                            aria-controls="survey-actions-menu-{{ $survey->id }}">
                                            <i class="fa-solid fa-ellipsis-vertical menu-icon" aria-hidden="true"></i>
                                            اقدامات
                                            <i class="fa-solid fa-chevron-down survey-actions-chevron menu-icon" aria-hidden="true"></i>
                                        </button>
                                        <div class="survey-actions-menu" id="survey-actions-menu-{{ $survey->id }}"
                                            role="menu" aria-label="اقدامات نظرسنجی">
                                            <div class="survey-actions-menu-item" role="none">
                                                @if (($survey->submitted_responses_count ?? $survey->responses_count ?? 0) > 0)
                                                    <a href="{{ route('admin.surveys.report', $survey) }}" role="menuitem"><i class="fa-solid fa-chart-pie menu-icon" aria-hidden="true"></i>مشاهده گزارش</a>
                                                @else
                                                    <button type="button" role="menuitem" class="is-muted" disabled><i class="fa-solid fa-chart-pie menu-icon" aria-hidden="true"></i>مشاهده گزارش</button>
                                                @endif
                                            </div>
                                            <div class="survey-actions-menu-item" role="none">
                                                <a href="{{ route('admin.surveys.edit', $survey) }}" role="menuitem"><i class="fa-solid fa-sliders menu-icon" aria-hidden="true"></i>تنظیمات</a>
                                            </div>
                                            <div class="survey-actions-menu-item" role="none">
                                                <a href="{{ route('admin.surveys.edit', $survey) }}#appearance" role="menuitem"><i class="fa-solid fa-palette menu-icon" aria-hidden="true"></i>تنظیمات ظاهری</a>
                                            </div>
                                            <div class="survey-actions-menu-item" role="none">
                                                <a href="{{ route('admin.surveys.questions.index', $survey) }}" role="menuitem"><i class="fa-solid fa-list-check menu-icon" aria-hidden="true"></i>طراحی سوالات</a>
                                            </div>
                                            <div class="survey-actions-menu-item" role="none">
                                                @if ($admin instanceof \App\Models\AdminUser && $admin->isAdmin())
                                                    <form method="POST" action="{{ route('admin.surveys.generate-link', $survey) }}" class="survey-actions-form">
                                                        @csrf
                                                        <button type="submit" role="menuitem"><i class="fa-solid fa-link menu-icon" aria-hidden="true"></i>ایجاد / به‌روزرسانی لینک</button>
                                                    </form>
                                                @elseif ($ownerNeedsManagerApproval && $isOwnerSupervisor)
                                                    @if ($survey->status === 'pending_approval')
                                                        <button type="button" role="menuitem" class="is-muted" disabled><i class="fa-solid fa-clock menu-icon" aria-hidden="true"></i>در انتظار تأیید مدیر</button>
                                                    @elseif ($survey->status === 'active')
                                                        <span class="survey-actions-hint" role="menuitem"><i class="fa-solid fa-circle-check menu-icon" aria-hidden="true"></i>نظرسنجی فعال است</span>
                                                    @elseif ($survey->status === 'closed')
                                                        <button type="button" role="menuitem" class="is-muted" disabled><i class="fa-solid fa-lock menu-icon" aria-hidden="true"></i>نظرسنجی بسته شده</button>
                                                    @else
                                                        <form method="POST" action="{{ route('admin.surveys.generate-link', $survey) }}" class="survey-actions-form">
                                                            @csrf
                                                            <button type="submit" role="menuitem"><i class="fa-solid fa-paper-plane menu-icon" aria-hidden="true"></i>ارسال برای تأیید مدیر</button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <form method="POST" action="{{ route('admin.surveys.generate-link', $survey) }}" class="survey-actions-form">
                                                        @csrf
                                                        <button type="submit" role="menuitem"><i class="fa-solid fa-link menu-icon" aria-hidden="true"></i>ایجاد لینک و فعال‌سازی</button>
                                                    </form>
                                                @endif
                                            </div>
                                            @if ($admin instanceof \App\Models\AdminUser && $admin->isAdmin() && $survey->status === 'pending_approval')
                                                <div class="survey-actions-menu-item" role="none">
                                                    <form method="POST" action="{{ route('admin.surveys.approve-publish', $survey) }}" class="survey-actions-form"
                                                        onsubmit="return confirm('انتشار این نظرسنجی تأیید شود؟');">
                                                        @csrf
                                                        <button type="submit" role="menuitem" class="is-success"><i class="fa-solid fa-check menu-icon" aria-hidden="true"></i>تأیید انتشار</button>
                                                    </form>
                                                </div>
                                                <div class="survey-actions-menu-item" role="none">
                                                    <button type="button" role="menuitem" class="is-danger" data-open-reject-modal
                                                        data-reject-url="{{ route('admin.surveys.reject-publish', $survey) }}"
                                                        data-reject-survey-id="{{ $survey->id }}"
                                                        data-survey-title="{{ e($survey->title) }}"><i class="fa-solid fa-xmark menu-icon" aria-hidden="true"></i>رد انتشار</button>
                                                </div>
                                            @endif
                                            @if ($survey->public_token)
                                                <div class="survey-link" role="none">
                                                    <div class="survey-link-row">
                                                        <span><i class="fa-solid fa-globe menu-icon" aria-hidden="true"></i>لینک عمومی</span>
                                                        <a href="{{ route('surveys.public.show', $survey->public_token) }}" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square menu-icon" aria-hidden="true"></i>باز کردن</a>
                                                    </div>
                                                    <span class="survey-link-url" dir="ltr">{{ route('surveys.public.show', $survey->public_token) }}</span>
                                                </div>
                                            @endif
                                            <div class="survey-actions-menu-item" role="none">
                                                @if (($survey->responses_records_count ?? 0) > 0)
                                                    <button type="button" role="menuitem" class="is-muted" disabled
                                                        title="به‌دلیل وجود پاسخ (ثبت‌شده یا پیش‌نویس) امکان حذف وجود ندارد."><i class="fa-solid fa-trash menu-icon" aria-hidden="true"></i>حذف نظرسنجی</button>
                                                @else
                                                    <form method="POST" action="{{ route('admin.surveys.destroy', $survey) }}" class="survey-actions-form"
                                                        onsubmit="return confirm('با حذف این نظرسنجی، همه سوالات آن نیز حذف می‌شود.\n\nآیا مطمئن هستید؟');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" role="menuitem" class="is-danger"><i class="fa-solid fa-trash menu-icon" aria-hidden="true"></i>حذف نظرسنجی</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="survey-empty-state" style="text-align:center; padding:2rem 1rem; color:var(--muted);">
                                    <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                                    هنوز نظرسنجی‌ای ثبت نشده است.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($surveys, 'links'))
                <div class="table-pagination">
                    {{ $surveys->links() }}
                </div>
            @endif
        </section>
    </div>

    <div class="modal" id="surveyGuideModal" aria-hidden="true">
        <div class="modal-dialog guide-modal-dialog">
            <div class="modal-header">
                <h3><i class="fa-solid fa-circle-question" aria-hidden="true"></i>راهنمای گام‌به‌گام ساخت نظرسنجی</h3>
                <button class="modal-close" type="button" data-close-modal>&times;</button>
            </div>
            <ol class="guide-steps">
                <li><i class="fa-solid fa-plus" aria-hidden="true"></i><strong>ایجاد نظرسنجی:</strong> روی «افزودن نظرسنجی» کلیک کنید، عنوان و واحد مربوطه را وارد کنید و ثبت را بزنید.</li>
                <li><i class="fa-solid fa-sliders" aria-hidden="true"></i><strong>تنظیمات پایه:</strong> از منوی اقدامات وارد «تنظیمات» شوید؛ وضعیت، بازه زمانی، سقف پاسخ، امکان ویرایش، مخاطبان و سایر گزینه‌ها را مشخص کنید.</li>
                <li><i class="fa-solid fa-palette" aria-hidden="true"></i><strong>تنظیمات ظاهری:</strong> از «تنظیمات ظاهری» رنگ‌ها، پیام‌ها، پس‌زمینه و ظاهر فرم عمومی را تنظیم کنید.</li>
                <li><i class="fa-solid fa-list-check" aria-hidden="true"></i><strong>طراحی سوالات:</strong> نوع سوال‌ها را انتخاب کنید (متنی، چندگزینه‌ای، تاریخ، آپلود فایل و ...)، اجباری بودن و قواعد هر سوال را تعریف کنید.</li>
                <li><i class="fa-solid fa-flask" aria-hidden="true"></i><strong>کنترل کیفیت:</strong> لینک عمومی را بسازید و فرم را با پاسخ نمونه تست کنید؛ نمایش و گزارش را بررسی کنید.</li>
                <li><i class="fa-solid fa-rocket" aria-hidden="true"></i><strong>انتشار:</strong> با گزینه «ایجاد/به‌روزرسانی لینک» نظرسنجی را فعال کنید (یا برای تایید مدیر ارسال کنید).</li>
                <li><i class="fa-solid fa-chart-line" aria-hidden="true"></i><strong>پایش و تحلیل:</strong> از «مشاهده گزارش» پاسخ‌ها را بررسی، ویرایش، خروجی اکسل بگیرید و در پایان وضعیت را ببندید.</li>
            </ol>
            <div class="guide-note">
                پیشنهاد: قبل از ارسال عمومی، یک سناریوی کامل را از ابتدا تا ثبت پاسخ و مشاهده گزارش اجرا کنید تا همه تنظیمات دسترسی و سوالات نهایی کنترل شود.
            </div>
            <div class="modal-actions" style="margin-top: 1rem;">
                <button class="primary" type="button" data-close-modal><i class="fa-solid fa-check" aria-hidden="true"></i>متوجه شدم</button>
            </div>
        </div>
    </div>

    {{-- Add Survey Modal --}}
<div class="modal" id="addSurveyModal" aria-hidden="true">
        <form method="POST" action="{{ route('admin.surveys.store') }}" class="modal-dialog" id="addSurveyForm">
            @csrf
            <div class="modal-header">
                <h3><i class="fa-solid fa-plus" aria-hidden="true"></i>افزودن نظرسنجی جدید</h3>
                <button class="modal-close" type="button" data-close-modal>&times;</button>
            </div>
            <p>نام نظرسنجی و واحد مربوطه را مشخص کنید؛ یادداشت کوتاه اختیاری است.</p>
            <div class="form-field">
                <label for="surveyNameInput">نام نظرسنجی</label>
                <input type="text" id="surveyNameInput" name="title" value="{{ old('title') }}"
                    placeholder="مثلاً رضایت از خدمات سازمان">
                @error('title', 'createSurvey')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </div>
            <div class="form-field">
                <label for="surveyUnitSelect">واحد مربوطه <span style="color:#dc2626">*</span></label>
                <select id="surveyUnitSelect" name="unit_id" required>
                    <option value="">انتخاب واحد</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->name }}</option>
                    @endforeach
                </select>
                @error('unit_id', 'createSurvey')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </div>
            <div class="form-field">
                <label for="surveyNotes">یادداشت کوتاه (اختیاری)</label>
                <textarea id="surveyNotes" rows="3" name="description" placeholder="هدف نظرسنجی یا نکات مهم ...">{{ old('description') }}</textarea>
                @error('description', 'createSurvey')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </div>
            <div class="modal-actions">
                <button class="primary" type="submit"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>ثبت و ادامه</button>
                <button class="ghost" type="button" data-close-modal><i class="fa-solid fa-xmark" aria-hidden="true"></i>انصراف</button>
            </div>
        </form>
    </div>

    <div class="modal" id="rejectPublishModal" aria-hidden="true">
        <form method="POST" id="rejectPublishForm" class="modal-dialog" action="">
            @csrf
            <div class="modal-header">
                <h3><i class="fa-solid fa-ban" aria-hidden="true"></i>رد درخواست انتشار</h3>
                <button class="modal-close" type="button" data-close-modal>&times;</button>
            </div>
            <p class="reject-publish-lead" id="rejectPublishSurveyLead"></p>
            <div class="form-field">
                <label for="rejection_reason">دلیل رد <span style="color:#dc2626">*</span></label>
                <textarea id="rejection_reason" name="rejection_reason" rows="4" maxlength="2000" required
                    placeholder="توضیح دهید چرا درخواست انتشار رد می‌شود...">{{ old('rejection_reason') }}</textarea>
                @error('rejection_reason')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </div>
            <div class="modal-actions">
                <button class="primary reject-submit" type="submit"><i class="fa-solid fa-ban" aria-hidden="true"></i>ثبت رد درخواست</button>
                <button class="ghost" type="button" data-close-modal><i class="fa-solid fa-xmark" aria-hidden="true"></i>انصراف</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.body;
            const addSurveyModal = document.getElementById('addSurveyModal');
            const openAddSurvey = document.getElementById('openAddSurvey');
            const surveyGuideModal = document.getElementById('surveyGuideModal');
            const openSurveyGuide = document.getElementById('openSurveyGuide');
            const rejectPublishModal = document.getElementById('rejectPublishModal');
            const rejectPublishForm = document.getElementById('rejectPublishForm');
            const rejectPublishLead = document.getElementById('rejectPublishSurveyLead');

            const dismissSurveyActionsMenus = () => {
                const ae = document.activeElement;
                if (ae && ae.closest && ae.closest('.survey-actions-dropdown')) {
                    ae.blur();
                }
            };

            const setBodyModalState = () => {
                const hasOpenModal = Array.from(document.querySelectorAll('.modal')).some((modal) =>
                    modal.classList.contains('open')
                );
                body.classList.toggle('modal-open', hasOpenModal);
            };

            const toggleModal = (modal, show) => {
                if (!modal) return;
                const willShow = Boolean(show);
                if (willShow) {
                    body.classList.add('modal-open');
                    dismissSurveyActionsMenus();
                }
                modal.classList.toggle('open', willShow);
                setBodyModalState();
            };

            if (openAddSurvey) {
                openAddSurvey.addEventListener('click', () => {
                    const addSurveyForm = document.getElementById('addSurveyForm');
                    if (addSurveyForm) {
                        addSurveyForm.reset();
                    }
                    toggleModal(addSurveyModal, true);
                });
            }

            if (openSurveyGuide) {
                openSurveyGuide.addEventListener('click', () => {
                    toggleModal(surveyGuideModal, true);
                });
            }

            const escapeHtml = (s) => {
                const d = document.createElement('div');
                d.textContent = s;
                return d.innerHTML;
            };

            const openRejectPublishModal = (openReject) => {
                if (!openReject || !rejectPublishModal || !rejectPublishForm) {
                    return;
                }
                const url = openReject.getAttribute('data-reject-url');
                const title = openReject.getAttribute('data-survey-title') || '';
                rejectPublishForm.setAttribute('action', url || '');
                if (rejectPublishLead) {
                    if (title) {
                        rejectPublishLead.innerHTML = 'نظرسنجی: <strong>' + escapeHtml(title) + '</strong>';
                    } else {
                        rejectPublishLead.textContent = '';
                    }
                }
                toggleModal(rejectPublishModal, true);
                requestAnimationFrame(() => {
                    const ta = document.getElementById('rejection_reason');
                    if (ta) {
                        try {
                            ta.focus({ preventScroll: true });
                        } catch (e) {
                            ta.focus();
                        }
                    }
                });
            };

            /* یک هندلر در فاز capture: جلوگیری از تداخل با سایر listenerها و حلقهٔ فوکوس/ری‌فلو هنگام باز شدن مودال رد */
            document.addEventListener(
                'click',
                (event) => {
                    const openReject = event.target.closest('[data-open-reject-modal]');
                    if (openReject && rejectPublishModal && rejectPublishForm) {
                        event.preventDefault();
                        event.stopPropagation();
                        event.stopImmediatePropagation();
                        openRejectPublishModal(openReject);
                        return;
                    }
                    const closeBtn = event.target.closest('[data-close-modal]');
                    if (closeBtn) {
                        const modal = closeBtn.closest('.modal');
                        toggleModal(modal, false);
                    }
                },
                true
            );

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    document.querySelectorAll('.modal.open').forEach((modal) => modal.classList.remove('open'));
                    setBodyModalState();
                }
            });

            @if ($errors->createSurvey->any())
                window.addEventListener('load', () => toggleModal(addSurveyModal, true));
            @endif

            const rejectPublishSurveyId = @json(session('reject_publish_survey_id'));
            const rejectPublishActionUrl = @json(session('reject_publish_action_url'));
            if (rejectPublishModal && rejectPublishForm && rejectPublishSurveyId) {
                const reopenBtn = document.querySelector(
                    '[data-open-reject-modal][data-reject-survey-id="' + rejectPublishSurveyId + '"]'
                );
                if (reopenBtn) {
                    requestAnimationFrame(() => openRejectPublishModal(reopenBtn));
                } else if (rejectPublishActionUrl) {
                    rejectPublishForm.setAttribute('action', rejectPublishActionUrl);
                    if (rejectPublishLead) {
                        rejectPublishLead.textContent =
                            'لطفاً دلیل رد را تکمیل کنید و دوباره ثبت کنید.';
                    }
                    requestAnimationFrame(() => {
                        toggleModal(rejectPublishModal, true);
                        requestAnimationFrame(() => {
                            const ta = document.getElementById('rejection_reason');
                            if (ta) {
                                try {
                                    ta.focus({ preventScroll: true });
                                } catch (e) {
                                    ta.focus();
                                }
                            }
                        });
                    });
                }
            }

        });
    </script>
@endsection