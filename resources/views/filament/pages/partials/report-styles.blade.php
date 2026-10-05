<style>
    .mi-rpt {
        --ink: #1c1917;
        --muted: #78716c;
        --line: #e7e5e4;
        --paper: #f6f3ef;
        --card: #ffffff;
        --red: #b00000;
        --red-soft: #fff4f4;
        color: var(--ink);
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .dark .mi-rpt {
        --ink: #f5f5f4;
        --muted: #a8a29e;
        --line: #3f3f46;
        --paper: #18181b;
        --card: #111113;
        --red: #ff5c5c;
        --red-soft: #3a1212;
    }

    .mi-rpt-toolbar,
    .mi-rpt-card,
    .mi-rpt-kpi,
    .mi-rpt-rank {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 18px;
    }

    .mi-rpt-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 14px 16px;
        padding: 14px 16px;
    }

    .mi-rpt-seg {
        display: inline-flex;
        gap: 4px;
        padding: 4px;
        border-radius: 14px;
        background: var(--paper);
    }

    .mi-rpt-seg button,
    .mi-rpt-btn {
        border: 0;
        cursor: pointer;
        font: inherit;
    }

    .mi-rpt-seg button {
        border-radius: 10px;
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 700;
        color: var(--muted);
        background: transparent;
    }

    .mi-rpt-seg button.is-on {
        background: var(--ink);
        color: #fff;
    }

    .dark .mi-rpt-seg button.is-on {
        background: #fafaf9;
        color: #1c1917;
    }

    .mi-rpt-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 150px;
    }

    .mi-rpt-field span,
    .mi-rpt-note {
        font-size: 12px;
        font-weight: 700;
        color: var(--muted);
    }

    .mi-rpt-field input,
    .mi-rpt-field select {
        height: 40px;
        border-radius: 12px;
        border: 1px solid var(--line);
        background: var(--paper);
        color: var(--ink);
        padding: 0 12px;
        font: inherit;
        font-size: 14px;
    }

    .mi-rpt-period {
        margin-inline-start: auto;
        text-align: end;
    }

    .mi-rpt-period strong {
        display: block;
        font-size: 15px;
        font-weight: 800;
    }

    .mi-rpt-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-inline-start: auto;
    }

    .mi-rpt-btn {
        height: 40px;
        border-radius: 12px;
        padding: 0 14px;
        font-size: 13px;
        font-weight: 800;
    }

    .mi-rpt-btn-ghost {
        background: var(--paper);
        color: var(--ink);
        border: 1px solid var(--line);
    }

    .mi-rpt-btn-solid {
        background: var(--red);
        color: #fff;
    }

    .mi-rpt-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .mi-rpt-kpis.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .mi-rpt-kpis.cols-8 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

    .mi-rpt-kpi {
        position: relative;
        padding: 18px 18px 16px 22px;
        overflow: hidden;
    }

    .mi-rpt-kpi::before {
        content: "";
        position: absolute;
        inset-inline-start: 0;
        top: 16px;
        bottom: 16px;
        width: 3px;
        border-radius: 3px;
        background: var(--red);
    }

    .mi-rpt-kpi b {
        display: block;
        font-size: 30px;
        line-height: 1;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.04em;
    }

    .mi-rpt-kpi span {
        display: block;
        margin-top: 8px;
        font-size: 13px;
        font-weight: 700;
        color: var(--muted);
    }

    .mi-rpt-ranks {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .mi-rpt-rank {
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .mi-rpt-rank header,
    .mi-rpt-card > header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .mi-rpt-who {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .mi-rpt-avatar {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: var(--red-soft);
        color: var(--red);
        font-weight: 800;
        flex: none;
    }

    .mi-rpt-who strong,
    .mi-rpt-card > header h2 {
        display: block;
        font-size: 16px;
        font-weight: 800;
        line-height: 1.3;
    }

    .mi-rpt-who em,
    .mi-rpt-card > header p {
        display: block;
        font-style: normal;
        font-size: 12px;
        color: var(--muted);
        font-weight: 600;
    }

    .mi-rpt-rank .score {
        text-align: end;
    }

    .mi-rpt-rank .score b {
        display: block;
        font-size: 28px;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }

    .mi-rpt-rank .score span,
    .mi-rpt-money {
        font-size: 12px;
        font-weight: 700;
        color: var(--muted);
    }

    .mi-rpt-money {
        font-size: 18px;
        color: var(--ink);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .mi-rpt-mini {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding-top: 12px;
        border-top: 1px solid var(--line);
        text-align: center;
    }

    .mi-rpt-mini b {
        display: block;
        font-size: 16px;
        font-variant-numeric: tabular-nums;
    }

    .mi-rpt-mini span {
        font-size: 11px;
        color: var(--muted);
        font-weight: 700;
    }

    .mi-rpt-card { overflow: hidden; }

    .mi-rpt-card > header {
        padding: 16px 18px;
        border-bottom: 1px solid var(--line);
    }

    .mi-rpt-scroll { overflow-x: auto; }

    .mi-rpt-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .mi-rpt-table th {
        text-align: right;
        padding: 12px 14px;
        font-size: 12px;
        font-weight: 800;
        color: var(--muted);
        background: var(--paper);
        white-space: nowrap;
    }

    .mi-rpt-table td {
        padding: 13px 14px;
        border-top: 1px solid var(--line);
        vertical-align: middle;
    }

    .mi-rpt-table tbody tr:hover td { background: var(--red-soft); }

    .mi-rpt-table .num {
        text-align: center;
        font-variant-numeric: tabular-nums;
        font-weight: 700;
    }

    .mi-rpt-table .end {
        text-align: end;
        font-variant-numeric: tabular-nums;
        font-weight: 800;
    }

    .mi-rpt-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 3px 10px;
        font-size: 12px;
        font-weight: 800;
        background: var(--paper);
        color: var(--ink);
        white-space: nowrap;
    }

    .mi-rpt-pill.ok { background: #ecfdf3; color: #166534; }
    .mi-rpt-pill.bad { background: #fff1f2; color: #be123c; }
    .mi-rpt-pill.info { background: #eff6ff; color: #1d4ed8; }
    .mi-rpt-pill.warn { background: #fffbeb; color: #b45309; }

    .dark .mi-rpt-pill.ok { background: #052e16; color: #86efac; }
    .dark .mi-rpt-pill.bad { background: #4c0519; color: #fda4af; }
    .dark .mi-rpt-pill.info { background: #172554; color: #93c5fd; }
    .dark .mi-rpt-pill.warn { background: #451a03; color: #fcd34d; }

    .mi-rpt-empty {
        padding: 36px 16px;
        text-align: center;
        color: var(--muted);
        font-weight: 700;
    }

    .mi-rpt-bar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .mi-rpt-bar i {
        display: block;
        width: 64px;
        height: 6px;
        border-radius: 99px;
        background: var(--paper);
        overflow: hidden;
        font-style: normal;
    }

    .mi-rpt-bar i b {
        display: block;
        height: 100%;
        background: var(--red);
        border-radius: inherit;
    }

    .mi-rpt-sub {
        font-size: 12px;
        color: var(--muted);
        margin-top: 2px;
    }

    .mi-rpt-log { max-height: 560px; overflow: auto; }

    .mi-rpt-log thead th { position: sticky; top: 0; z-index: 1; }

    @media (max-width: 1100px) {
        .mi-rpt-kpis,
        .mi-rpt-kpis.cols-3,
        .mi-rpt-ranks { grid-template-columns: 1fr 1fr; }
        .mi-rpt-period, .mi-rpt-actions { margin-inline-start: 0; }
    }

    @media (max-width: 720px) {
        .mi-rpt-kpis,
        .mi-rpt-kpis.cols-3,
        .mi-rpt-kpis.cols-8,
        .mi-rpt-ranks { grid-template-columns: 1fr; }
    }
</style>
