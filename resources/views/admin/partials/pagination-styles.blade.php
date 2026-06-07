<style>
    .admin-pagination {
        margin-top: 1rem;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 22px;
        padding: 1rem 1.15rem;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
    }

    .admin-pagination__nav {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
    }

    .admin-pagination__summary {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem 1rem;
        color: var(--muted);
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .admin-pagination__summary p {
        margin: 0;
    }

    .admin-pagination__summary strong {
        color: var(--slate);
        font-weight: 700;
    }

    .admin-pagination__status {
        background: rgba(15, 23, 42, 0.04);
        border-radius: 999px;
        padding: 0.25rem 0.85rem;
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .admin-pagination__controls {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        flex-wrap: wrap;
    }

    .admin-pagination__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        min-height: 2.65rem;
        padding: 0.55rem 1rem;
        border-radius: 14px;
        border: 1px solid rgba(15, 23, 42, 0.12);
        background: #fff;
        color: var(--slate);
        font-weight: 600;
        font-size: 0.9rem;
        line-height: 1.2;
        text-decoration: none;
        transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        white-space: nowrap;
    }

    .admin-pagination__btn--nav:hover:not(.is-disabled) {
        border-color: rgba(214, 17, 25, 0.35);
        color: var(--primary);
        background: rgba(214, 17, 25, 0.05);
    }

    .admin-pagination__btn--nav.is-disabled {
        opacity: 0.45;
        cursor: not-allowed;
        background: rgba(15, 23, 42, 0.03);
    }

    .admin-pagination__pages {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        flex-wrap: wrap;
        max-width: 100%;
    }

    .admin-pagination__page,
    .admin-pagination__ellipsis {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.45rem;
        height: 2.45rem;
        padding: 0 0.55rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        line-height: 1;
        text-decoration: none;
    }

    .admin-pagination__page {
        border: 1px solid rgba(15, 23, 42, 0.1);
        background: #fff;
        color: var(--slate);
        transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
    }

    .admin-pagination__page:hover {
        border-color: rgba(214, 17, 25, 0.35);
        color: var(--primary);
        background: rgba(214, 17, 25, 0.05);
    }

    .admin-pagination__page.is-active {
        border-color: transparent;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
        box-shadow: 0 8px 18px rgba(214, 17, 25, 0.25);
        cursor: default;
    }

    .admin-pagination__ellipsis {
        color: var(--muted);
        min-width: auto;
        padding: 0 0.2rem;
        user-select: none;
    }

    @media (min-width: 768px) {
        .admin-pagination__nav {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .admin-pagination__controls {
            justify-content: flex-end;
            flex-wrap: nowrap;
        }
    }

    @media (max-width: 520px) {
        .admin-pagination {
            padding: 0.85rem 0.75rem;
        }

        .admin-pagination__summary {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }

        .admin-pagination__status {
            align-self: center;
        }

        .admin-pagination__controls {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 0.45rem;
            align-items: center;
        }

        .admin-pagination__btn--nav:first-child {
            justify-self: start;
        }

        .admin-pagination__btn--nav:last-child {
            justify-self: end;
        }

        .admin-pagination__pages {
            grid-column: 1 / -1;
            order: 3;
            overflow-x: auto;
            flex-wrap: nowrap;
            justify-content: flex-start;
            padding-bottom: 0.15rem;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .admin-pagination__btn {
            min-height: 2.45rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
        }
    }
</style>
