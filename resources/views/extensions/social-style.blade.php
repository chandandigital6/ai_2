<style>
    .sp-shell {
        --sp-ink: #183d33;
        --sp-muted: #677b73;
        --sp-line: #dfe9e3;
        display: grid;
        gap: 24px;
        color: var(--sp-ink);
        min-width: 0
    }

    .sp-shell [hidden] {
        display: none !important
    }

    .sp-shell form {
        margin: 0
    }

    .sp-shell fieldset {
        min-width: 0;
        border: 0;
        padding: 0;
        margin: 0
    }

    .sp-shell fieldset:disabled {
        opacity: .55
    }

    .sp-shell .g-btn {
        white-space: normal;
        text-align: center
    }

    .sp-shell h2,
    .sp-shell h3,
    .sp-shell p {
        margin: 0
    }

    .sp-shell h2 {
        font-size: 21px;
        font-weight: 650;
        letter-spacing: -.5px;
        line-height: 1.3
    }

    .sp-shell h3 {
        font-size: 17px;
        font-weight: 650;
        line-height: 1.4;
        overflow-wrap: anywhere
    }

    .sp-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        background: #164f3f;
        color: #fff;
        padding: 32px;
        border-radius: 20px
    }

    .sp-hero h2 {
        font-size: 29px;
        margin: 8px 0
    }

    .sp-hero p {
        color: #cfdfd6;
        font-size: 14px;
        line-height: 1.7
    }

    .sp-eyebrow {
        display: block;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1.6px;
        line-height: 1.6;
        color: #668377
    }

    .sp-hero .sp-eyebrow {
        color: #b6cfa9
    }

    .sp-count {
        flex-shrink: 0;
        display: grid;
        text-align: center;
        background: #ffffff12;
        border: 1px solid #ffffff24;
        border-radius: 16px;
        padding: 18px 24px
    }

    .sp-count strong {
        font-size: 32px;
        line-height: 1.1
    }

    .sp-count span {
        font-size: 11px;
        margin-top: 6px;
        color: #d8e7dd
    }

    .sp-panel {
        background: #fff;
        border: 1px solid var(--sp-line);
        border-radius: 20px;
        padding: 26px;
        min-width: 0;
        box-shadow: 0 5px 20px #163d3304
    }

    .sp-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px
    }

    .sp-heading h2 {
        margin: 5px 0 8px
    }

    .sp-heading p,
    .sp-empty-history p {
        font-size: 13px;
        line-height: 1.7;
        color: var(--sp-muted)
    }

    .sp-account-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px
    }

    .sp-account {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        border: 1px solid var(--sp-line);
        border-radius: 16px;
        padding: 20px;
        background: #fcfdfb;
        min-width: 0
    }

    .sp-account form {
        margin-top: auto;
        padding-top: 5px
    }

    .sp-platform {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        height: 42px;
        width: 42px;
        border-radius: 12px;
        color: #fff;
        font-size: 27px;
        font-family: Arial, sans-serif;
        font-weight: 700
    }

    .sp-facebook {
        background: #1877f2
    }

    .sp-instagram {
        background: linear-gradient(135deg, #763ecb, #d6367b, #ee9b45)
    }

    .sp-small {
        height: 30px;
        width: 30px;
        border-radius: 9px;
        font-size: 20px
    }

    .sp-row {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        width: 100%
    }

    .sp-row .sp-pill {
        margin-left: auto
    }

    .sp-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 10px;
        background: #eff2f0;
        color: #52665c;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.4
    }

    .sp-ok {
        background: #e7f5eb;
        color: #256341
    }

    .sp-warn {
        background: #fff0dc;
        color: #8c521c
    }

    .sp-muted,
    .sp-account p {
        font-size: 12px;
        line-height: 1.6;
        color: var(--sp-muted);
        overflow-wrap: anywhere
    }

    .sp-shell .sp-help {
        font-size: 12px;
        line-height: 1.7;
        color: var(--sp-muted);
        margin-top: 12px
    }

    .sp-text-button {
        color: #19694d;
        font-size: 12px;
        font-weight: 600;
        text-decoration: underline;
        text-underline-offset: 3px;
        background: transparent;
        border: 0;
        cursor: pointer
    }

    .sp-selection {
        border-color: #a5c7b6;
        background: #fafff9
    }

    .sp-notice {
        border: 1px solid #b4d5c2;
        background: #ecf7ef;
        color: #245d3e;
        border-radius: 12px;
        padding: 16px 18px;
        font-size: 13px;
        line-height: 1.7;
        overflow-wrap: anywhere
    }

    .sp-warning {
        background: #fffaee;
        border-color: #eddbb5;
        color: #805520
    }

    .sp-compose-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
        gap: 24px;
        align-items: start
    }

    .sp-label {
        font-size: 13px;
        font-weight: 650;
        margin-bottom: 10px
    }

    .sp-channel-list {
        display: grid;
        gap: 8px;
        margin-bottom: 18px
    }

    .sp-channel {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        border: 1px solid var(--sp-line);
        border-radius: 11px;
        cursor: pointer;
        font-size: 13px;
        min-width: 0
    }

    .sp-channel:has(input:checked) {
        background: #edf8ef;
        border-color: #77aa8b
    }

    .sp-channel strong {
        display: block;
        overflow-wrap: anywhere
    }

    .sp-channel small,
    .sp-preview-head small,
    .sp-result small {
        display: block;
        font-size: 11px;
        color: var(--sp-muted);
        margin-top: 4px
    }

    .sp-channel input,
    .sp-weekdays input {
        width: 16px;
        height: 16px;
        accent-color: #166345;
        flex-shrink: 0
    }

    .sp-shell .ex-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px
    }

    .sp-shell .ex-field {
        line-height: 1.5
    }

    .sp-shell .ex-field input,
    .sp-shell .ex-field textarea,
    .sp-shell .ex-field select {
        box-sizing: border-box;
        min-width: 0;
        background: #fcfdfb;
        font-weight: 400
    }

    .sp-shell .ex-field textarea {
        resize: vertical
    }

    .sp-shell input:focus-visible,
    .sp-shell textarea:focus-visible,
    .sp-shell select:focus-visible,
    .sp-shell button:focus-visible,
    .sp-shell a:focus-visible {
        outline: 2px solid #38976c;
        outline-offset: 3px
    }

    .sp-preview h2 {
        margin: 6px 0 22px
    }

    .sp-preview-head {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px
    }

    .sp-avatar {
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #e9f1e8;
        color: #24573c;
        font-weight: 700;
        flex-shrink: 0
    }

    .sp-preview-head>div {
        min-width: 0;
        overflow-wrap: anywhere
    }

    .sp-preview-caption {
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        font-size: 14px;
        line-height: 1.7;
        margin: 18px 0 !important
    }

    .sp-preview img {
        display: block;
        width: 100%;
        height: auto;
        max-height: 420px;
        object-fit: contain;
        border-radius: 12px
    }

    .sp-preview-placeholder {
        display: grid;
        place-items: center;
        min-height: 180px;
        background: #f0f4ed;
        border: 1px dashed #c8d5c4;
        border-radius: 12px;
        font-size: 12px;
        color: #789076
    }

    .sp-details-title {
        cursor: pointer;
        font-size: 18px;
        font-weight: 650
    }

    .sp-details-title span {
        display: block;
        font-size: 12px;
        font-weight: 400;
        color: var(--sp-muted);
        margin-top: 5px
    }

    .sp-weekdays {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin: 12px 0
    }

    .sp-weekdays label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        padding: 8px;
        background: #f3f6f0;
        border-radius: 8px
    }

    .sp-schedule {
        display: flex;
        gap: 14px;
        align-items: center;
        flex-wrap: wrap;
        border-top: 1px solid var(--sp-line);
        padding-top: 18px;
        margin-top: 18px;
        font-size: 13px
    }

    .sp-schedule p {
        font-size: 12px;
        color: var(--sp-muted);
        margin-top: 4px
    }

    .sp-history-item {
        padding: 22px 0;
        border-top: 1px solid var(--sp-line)
    }

    .sp-history-item time {
        font-size: 11px;
        color: var(--sp-muted)
    }

    .sp-history-item .sp-row .sp-pill {
        margin-left: 0
    }

    .sp-history-item .sp-row strong {
        font-size: 13px
    }

    .sp-result {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
        background: #f6f8f3;
        padding: 12px 14px;
        border-radius: 10px;
        margin-bottom: 8px;
        font-size: 13px
    }

    .sp-result>div {
        min-width: 0;
        overflow-wrap: anywhere
    }

    .sp-error-text,
    .sp-form-error {
        color: #a33636 !important;
        font-size: 12px;
        line-height: 1.7;
        margin-top: 6px !important
    }

    .sp-empty-history {
        text-align: center;
        padding: 35px 15px
    }

    .sp-empty-history h3 {
        margin-bottom: 8px
    }

    .sp-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        max-width: min(420px, calc(100vw - 32px));
        background: #6c2929;
        color: #fff;
        padding: 16px 20px;
        border-radius: 12px;
        z-index: 100;
        box-shadow: 0 8px 30px #0002;
        font-size: 13px;
        line-height: 1.6
    }

    @media(prefers-color-scheme:dark) {
        .dark .sp-shell {
            --sp-ink: #dcebe1;
            --sp-muted: #a1b5a9;
            --sp-line: #3c5147
        }

        .dark .sp-panel,
        .dark .sp-account,
        .dark .sp-shell .ex-field input,
        .dark .sp-shell .ex-field textarea,
        .dark .sp-shell .ex-field select {
            background: #1d3028
        }

        .dark .sp-selection,
        .dark .sp-result,
        .dark .sp-preview-placeholder,
        .dark .sp-weekdays label {
            background: #243d30
        }

        .dark .sp-text-button {
            color: #9ed2ac
        }
    }

    @media(max-width:1000px) {
        .sp-compose-layout {
            grid-template-columns: minmax(0, 1fr)
        }

        .sp-preview {
            max-width: 100%
        }
    }

    @media(max-width:600px) {
        .sp-hero {
            padding: 23px;
            align-items: flex-start;
            flex-direction: column
        }

        .sp-hero h2 {
            font-size: 25px
        }

        .sp-count {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px
        }

        .sp-count span {
            margin: 0
        }

        .sp-count strong {
            font-size: 24px
        }

        .sp-panel {
            padding: 18px;
            border-radius: 16px
        }

        .sp-heading {
            flex-direction: column;
            gap: 14px
        }

        .sp-account-grid,
        .sp-shell .ex-grid {
            grid-template-columns: minmax(0, 1fr)
        }

        .sp-account {
            padding: 17px
        }

        .sp-shell {
            gap: 18px
        }

        .sp-heading>form,
        .sp-heading>form .g-btn {
            width: 100%
        }

        .sp-result {
            align-items: flex-start
        }

        .sp-toast {
            right: 16px;
            bottom: 16px
        }
    }
</style>
