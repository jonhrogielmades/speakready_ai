<style>
    html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero,
    html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero,
    :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero {
        display: block !important;
        min-height: 80px !important;
        margin-bottom: 14px !important;
        border: 1px solid rgba(147, 197, 253, 0.48) !important;
        border-radius: 8px !important;
        background:
            radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
            radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
            linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
        box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
        color: #f8fbff !important;
        overflow: hidden !important;
        position: relative !important;
    }

    html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero::before,
    html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero::after,
    html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero::before,
    html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero::after,
    :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero::before,
    :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero::after {
        content: none !important;
        display: none !important;
    }

    html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-inner,
    html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-inner,
    :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-inner {
        display: flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: 12px !important;
        min-height: 80px !important;
        padding: 10px 138px 10px 14px !important;
    }

    html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-copy,
    html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-copy,
    :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-copy {
        min-width: 0 !important;
        width: auto !important;
    }

    html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-title,
    html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-title,
    :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-title {
        display: block !important;
        margin: 0 0 3px !important;
        background: none !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        font-size: 1.16rem !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
        line-height: 1.12 !important;
        text-shadow: none !important;
        text-transform: none !important;
        white-space: normal !important;
    }

    html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-subtitle,
    html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-subtitle,
    :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-subtitle {
        max-width: 760px !important;
        margin: 0 !important;
        color: rgba(248, 251, 255, 0.92) !important;
        -webkit-text-fill-color: rgba(248, 251, 255, 0.92) !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        line-height: 1.4 !important;
    }

    html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon,
    html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon,
    :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon {
        box-sizing: border-box !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 0 0 38px !important;
        width: 38px !important;
        height: 38px !important;
        min-width: 38px !important;
        border: 1px solid rgba(255, 255, 255, 0.62) !important;
        border-radius: 8px !important;
        background: rgba(255, 255, 255, 0.94) !important;
        color: #1d4ed8 !important;
        -webkit-text-fill-color: #1d4ed8 !important;
        font-size: 0.95rem !important;
        box-shadow: none !important;
    }

    html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon :is(i, svg, svg *),
    html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon :is(i, svg, svg *),
    :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon :is(i, svg, svg *) {
        color: currentColor !important;
        -webkit-text-fill-color: currentColor !important;
        stroke: currentColor !important;
    }

    html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-art,
    html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-art,
    :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-art {
        display: block !important;
        position: absolute !important;
        z-index: 0 !important;
        right: 10px !important;
        bottom: -8px !important;
        width: 126px !important;
        height: auto !important;
        opacity: 0.94 !important;
        pointer-events: none !important;
        user-select: none !important;
        filter: drop-shadow(0 16px 24px rgba(37, 99, 235, 0.18)) !important;
        transform-origin: 50% 60% !important;
        animation: practiceHeroArtFloat 5.5s ease-in-out infinite !important;
    }

    :is(#practice-plan-page, #practice-calendar-page) .practice-hero-art .practice-art-panel {
        transform-origin: 50% 50%;
        animation: practiceHeroPanelBreathe 5.5s ease-in-out infinite;
    }

    :is(#practice-plan-page, #practice-calendar-page) .practice-hero-art .practice-art-line {
        transform-origin: 50% 50%;
        animation: practiceHeroLineSlide 3.8s ease-in-out infinite;
    }

    :is(#practice-plan-page, #practice-calendar-page) .practice-hero-art .practice-art-dot,
    :is(#practice-plan-page, #practice-calendar-page) .practice-hero-art .practice-art-cell {
        transform-origin: center;
        animation: practiceHeroPulse 3.2s ease-in-out infinite;
    }

    :is(#practice-plan-page, #practice-calendar-page) .practice-hero-art .practice-art-check {
        transform-origin: 164px 50px;
        animation: practiceHeroCheckPulse 2.6s ease-in-out infinite;
    }

    :is(#practice-plan-page, #practice-calendar-page) .practice-hero-art .practice-art-spark {
        transform-origin: center;
        animation: practiceHeroSparkDrift 3.2s ease-in-out infinite;
    }

    :is(#practice-plan-page, #practice-calendar-page) .practice-hero-art .practice-art-cell:nth-of-type(2n),
    :is(#practice-plan-page, #practice-calendar-page) .practice-hero-art .practice-art-dot:nth-of-type(2n),
    :is(#practice-plan-page, #practice-calendar-page) .practice-hero-art .practice-art-spark:nth-last-child(1) {
        animation-delay: 0.28s;
    }

    @keyframes practiceHeroArtFloat {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg) scale(1); }
        35% { transform: translate3d(0, -6px, 0) rotate(1.3deg) scale(1.012); }
        70% { transform: translate3d(-3px, -2px, 0) rotate(-0.8deg) scale(1.004); }
    }

    @keyframes practiceHeroPanelBreathe {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.018); opacity: 0.94; }
    }

    @keyframes practiceHeroLineSlide {
        0%, 100% { transform: translateX(0); opacity: 0.92; }
        50% { transform: translateX(3px); opacity: 1; }
    }

    @keyframes practiceHeroPulse {
        0%, 100% { transform: scale(1); opacity: 0.9; }
        50% { transform: scale(1.08); opacity: 1; }
    }

    @keyframes practiceHeroCheckPulse {
        0%, 100% { transform: scale(1); opacity: 0.95; }
        50% { transform: scale(1.12); opacity: 1; }
    }

    @keyframes practiceHeroSparkDrift {
        0%, 100% { transform: translate3d(0, 0, 0) scale(1); opacity: 0.66; }
        50% { transform: translate3d(2px, -5px, 0) scale(1.18); opacity: 1; }
    }

    @media (max-width: 767px) {
        html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero,
        html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero,
        :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero.setup-hero {
            min-height: 69px !important;
            margin-bottom: 12px !important;
        }

        html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-inner,
        html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-inner,
        :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-inner {
            gap: 8px !important;
            min-height: 69px !important;
            padding: 8px 72px 8px 10px !important;
        }

        html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon,
        html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon,
        :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon {
            flex-basis: 28px !important;
            width: 28px !important;
            height: 28px !important;
            min-width: 28px !important;
            font-size: 0.8rem !important;
        }

        html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-title,
        html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-title,
        :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-title {
            font-size: 0.72rem !important;
            white-space: nowrap !important;
        }

        html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-subtitle,
        html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-subtitle,
        :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-subtitle {
            font-size: 0.49rem !important;
            line-height: 1.32 !important;
        }

        html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-art,
        html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-art,
        :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-art {
            right: -5px !important;
            bottom: -2px !important;
            width: 72px !important;
        }
    }

    @media (max-width: 390px) {
        html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-inner,
        html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-inner,
        :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-inner {
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }

        html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon,
        html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon,
        :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-icon {
            flex-basis: 27px !important;
            width: 27px !important;
            height: 27px !important;
            min-width: 27px !important;
        }

        html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-title,
        html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-title,
        :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-title {
            font-size: 0.68rem !important;
        }

        html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-subtitle,
        html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-subtitle,
        :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .setup-hero-subtitle {
            font-size: 0.46rem !important;
        }

        html body #dashboard .db-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-art,
        html body #mob-content :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-art,
        :is(#practice-plan-page, #practice-calendar-page) .practice-page-hero .practice-hero-art {
            width: 66px !important;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        :is(#practice-plan-page, #practice-calendar-page) .practice-hero-art,
        :is(#practice-plan-page, #practice-calendar-page) .practice-hero-art * {
            animation: none !important;
        }
    }

    /* Standalone practice pages should fill the same content lane as the other user pages. */
    html body #dashboard .db-content #practice-plan-page,
    html body #mob-content #practice-plan-page,
    #practice-plan-page {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        box-sizing: border-box !important;
    }

    html body #dashboard .db-content #practice-plan-page > :is(.setup-hero, #practicePlanActions, .row),
    html body #mob-content #practice-plan-page > :is(.setup-hero, #practicePlanActions, .row),
    #practice-plan-page > :is(.setup-hero, #practicePlanActions, .row) {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        box-sizing: border-box !important;
    }

    html body #dashboard .db-content :is(#practicePlanActions, #practiceCalendarActions),
    html body #mob-content :is(#practicePlanActions, #practiceCalendarActions),
    :is(#practicePlanActions, #practiceCalendarActions) {
        justify-content: flex-end !important;
        gap: 6px !important;
        margin: 0 0 10px !important;
    }

    /* Compact practice page action buttons to match the other user pages. */
    html body #dashboard .db-content :is(#practicePlanActions, #practiceCalendarActions) .btn,
    html body #mob-content :is(#practicePlanActions, #practiceCalendarActions) .btn,
    :is(#practicePlanActions, #practiceCalendarActions) .btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 28px !important;
        padding: 5px 8px !important;
        border-radius: 7px !important;
        gap: 5px !important;
        font-size: 0.62rem !important;
        line-height: 1.1 !important;
        font-weight: 900 !important;
        white-space: nowrap !important;
    }

    html body #dashboard .db-content :is(#practicePlanActions, #practiceCalendarActions) .btn i,
    html body #mob-content :is(#practicePlanActions, #practiceCalendarActions) .btn i,
    :is(#practicePlanActions, #practiceCalendarActions) .btn i {
        margin-right: 0 !important;
        font-size: 0.66rem !important;
        line-height: 1 !important;
    }

    @media (max-width: 390px) {
        html body #dashboard .db-content :is(#practicePlanActions, #practiceCalendarActions) .btn,
        html body #mob-content :is(#practicePlanActions, #practiceCalendarActions) .btn,
        :is(#practicePlanActions, #practiceCalendarActions) .btn {
            min-height: 26px !important;
            padding: 4px 7px !important;
            font-size: 0.56rem !important;
        }

        html body #dashboard .db-content :is(#practicePlanActions, #practiceCalendarActions) .btn i,
        html body #mob-content :is(#practicePlanActions, #practiceCalendarActions) .btn i,
        :is(#practicePlanActions, #practiceCalendarActions) .btn i {
            font-size: 0.6rem !important;
        }
    }

    @media (min-width: 768px) {
        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-list,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-list,
        #practice-plan-page #personalized-practice-plan .practice-plan-list {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            grid-auto-rows: 1fr !important;
            gap: 14px !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-row,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-row,
        #practice-plan-page #personalized-practice-plan .practice-plan-row {
            grid-template-columns: 54px minmax(0, 1fr) !important;
            align-items: flex-start !important;
            gap: 16px !important;
            min-height: 188px !important;
            height: 100% !important;
            padding: 18px !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-icon,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-icon,
        #practice-plan-page #personalized-practice-plan .practice-plan-icon {
            width: 54px !important;
            height: 54px !important;
            flex-basis: 54px !important;
            border-radius: 14px !important;
            font-size: 1.35rem !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-top,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-top,
        #practice-plan-page #personalized-practice-plan .practice-plan-top {
            gap: 8px !important;
            margin-bottom: 8px !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-step,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-step,
        #practice-plan-page #personalized-practice-plan .practice-plan-step {
            padding: 6px 11px !important;
            font-size: 0.78rem !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-title,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-title,
        #practice-plan-page #personalized-practice-plan .practice-plan-title {
            font-size: 1rem !important;
            line-height: 1.22 !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-text,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-text,
        #practice-plan-page #personalized-practice-plan .practice-plan-text {
            font-size: 0.86rem !important;
            line-height: 1.36 !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks,
        #practice-plan-page #personalized-practice-plan .practice-plan-tasks {
            gap: 6px !important;
            margin-top: 12px !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks li,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks li,
        #practice-plan-page #personalized-practice-plan .practice-plan-tasks li {
            gap: 8px !important;
            font-size: 0.82rem !important;
            line-height: 1.3 !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks li i,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks li i,
        #practice-plan-page #personalized-practice-plan .practice-plan-tasks li i {
            width: 20px !important;
            height: 20px !important;
            flex-basis: 20px !important;
            border-radius: 7px !important;
            font-size: 0.68rem !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-footer,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-footer,
        #practice-plan-page #personalized-practice-plan .practice-plan-footer {
            gap: 10px !important;
            margin-top: 14px !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-pill,
        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-link,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-pill,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-link,
        #practice-plan-page #personalized-practice-plan .practice-plan-pill,
        #practice-plan-page #personalized-practice-plan .practice-plan-link {
            font-size: 0.78rem !important;
        }
    }

    @media (min-width: 768px) and (max-width: 1080px) {
        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-row,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-row,
        #practice-plan-page #personalized-practice-plan .practice-plan-row {
            grid-template-columns: 46px minmax(0, 1fr) !important;
            gap: 12px !important;
            padding: 15px !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-icon,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-icon,
        #practice-plan-page #personalized-practice-plan .practice-plan-icon {
            width: 46px !important;
            height: 46px !important;
            flex-basis: 46px !important;
            font-size: 1.15rem !important;
        }
    }

    /* Final practice plan panel polish: full-width panel with compact card sizing. */
    html body #dashboard .db-content #practice-plan-page #personalized-practice-plan,
    html body #mob-content #practice-plan-page #personalized-practice-plan,
    #practice-plan-page #personalized-practice-plan {
        --bs-gutter-x: 0 !important;
        --bs-gutter-y: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 0 14px !important;
        box-sizing: border-box !important;
    }

    html body #dashboard .db-content #practice-plan-page #personalized-practice-plan > .col-12,
    html body #mob-content #practice-plan-page #personalized-practice-plan > .col-12,
    #practice-plan-page #personalized-practice-plan > .col-12 {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        box-sizing: border-box !important;
    }

    html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-panel,
    html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-panel,
    #practice-plan-page #personalized-practice-plan .practice-plan-panel {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 12px !important;
        border-radius: 10px !important;
        box-sizing: border-box !important;
    }

    html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-heading,
    html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-heading,
    #practice-plan-page #personalized-practice-plan .practice-plan-heading {
        display: grid !important;
        grid-template-columns: 34px minmax(0, 1fr) !important;
        gap: 8px !important;
        align-items: center !important;
        margin: 0 0 10px !important;
        padding: 0 !important;
    }

    html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-heading-icon,
    html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-icon,
    html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-heading-icon,
    html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-icon,
    #practice-plan-page #personalized-practice-plan .practice-plan-heading-icon,
    #practice-plan-page #personalized-practice-plan .practice-plan-icon {
        width: 34px !important;
        height: 34px !important;
        min-width: 34px !important;
        flex: 0 0 34px !important;
        border-radius: 10px !important;
        font-size: 0.84rem !important;
    }

    html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-heading-title,
    html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-heading-title,
    #practice-plan-page #personalized-practice-plan .practice-plan-heading-title {
        margin: 0 0 3px !important;
        font-size: 0.94rem !important;
        font-weight: 900 !important;
        line-height: 1.15 !important;
    }

    html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-heading-text,
    html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-heading-text,
    #practice-plan-page #personalized-practice-plan .practice-plan-heading-text {
        margin: 0 !important;
        font-size: 0.68rem !important;
        line-height: 1.25 !important;
    }

    @media (min-width: 768px) {
        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-list,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-list,
        #practice-plan-page #personalized-practice-plan .practice-plan-list {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            grid-auto-rows: 1fr !important;
            gap: 8px !important;
            width: 100% !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-row,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-row,
        #practice-plan-page #personalized-practice-plan .practice-plan-row {
            display: grid !important;
            grid-template-columns: 34px minmax(0, 1fr) !important;
            align-items: flex-start !important;
            gap: 8px !important;
            min-height: 104px !important;
            height: 100% !important;
            padding: 9px !important;
            border-radius: 9px !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-top,
        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-footer,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-top,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-footer,
        #practice-plan-page #personalized-practice-plan .practice-plan-top,
        #practice-plan-page #personalized-practice-plan .practice-plan-footer {
            gap: 6px !important;
            margin: 0 0 4px !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-title,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-title,
        #practice-plan-page #personalized-practice-plan .practice-plan-title {
            font-size: 0.82rem !important;
            font-weight: 900 !important;
            line-height: 1.15 !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-text,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-text,
        #practice-plan-page #personalized-practice-plan .practice-plan-text {
            margin: 0 !important;
            font-size: 0.66rem !important;
            line-height: 1.28 !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks,
        #practice-plan-page #personalized-practice-plan .practice-plan-tasks {
            display: grid !important;
            gap: 4px !important;
            margin: 6px 0 5px !important;
            padding: 0 !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks li,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks li,
        #practice-plan-page #personalized-practice-plan .practice-plan-tasks li {
            gap: 6px !important;
            font-size: 0.64rem !important;
            line-height: 1.2 !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks li i,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-tasks li i,
        #practice-plan-page #personalized-practice-plan .practice-plan-tasks li i {
            width: 16px !important;
            height: 16px !important;
            min-width: 16px !important;
            flex-basis: 16px !important;
            font-size: 0.56rem !important;
        }

        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-pill,
        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-step,
        html body #dashboard .db-content #practice-plan-page #personalized-practice-plan .practice-plan-link,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-pill,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-step,
        html body #mob-content #practice-plan-page #personalized-practice-plan .practice-plan-link,
        #practice-plan-page #personalized-practice-plan .practice-plan-pill,
        #practice-plan-page #personalized-practice-plan .practice-plan-step,
        #practice-plan-page #personalized-practice-plan .practice-plan-link {
            min-height: 20px !important;
            padding: 4px 7px !important;
            font-size: 0.6rem !important;
            line-height: 1 !important;
            white-space: nowrap !important;
        }
    }

    /* Final activity calendar panel polish: compact stats, days, and footer action. */
    html body #dashboard .db-content #practice-calendar-page,
    html body #mob-content #practice-calendar-page,
    #practice-calendar-page {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        box-sizing: border-box !important;
    }

    html body #dashboard .db-content #practice-calendar-page > :is(.setup-hero, #practiceCalendarActions, .row),
    html body #mob-content #practice-calendar-page > :is(.setup-hero, #practiceCalendarActions, .row),
    #practice-calendar-page > :is(.setup-hero, #practiceCalendarActions, .row) {
        --bs-gutter-x: 0 !important;
        --bs-gutter-y: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        box-sizing: border-box !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar,
    html body #mob-content #practice-calendar-page #activity-calendar,
    #practice-calendar-page #activity-calendar {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        box-sizing: border-box !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-panel,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-panel,
    #practice-calendar-page #activity-calendar .activity-panel {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 12px !important;
        border-radius: 10px !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-heading,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-heading,
    #practice-calendar-page #activity-calendar .activity-heading {
        display: grid !important;
        grid-template-columns: 34px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 8px !important;
        margin: 0 0 10px !important;
        padding: 0 !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-heading-icon,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-heading-icon,
    #practice-calendar-page #activity-calendar .activity-heading-icon {
        width: 34px !important;
        height: 34px !important;
        min-width: 34px !important;
        flex: 0 0 34px !important;
        border-radius: 10px !important;
        font-size: 0.84rem !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-title,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-title,
    #practice-calendar-page #activity-calendar .activity-title {
        margin: 0 0 3px !important;
        font-size: 0.94rem !important;
        font-weight: 900 !important;
        line-height: 1.15 !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-subtitle,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-subtitle,
    #practice-calendar-page #activity-calendar .activity-subtitle {
        margin: 0 !important;
        font-size: 0.68rem !important;
        line-height: 1.25 !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-summary-grid,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-summary-grid,
    #practice-calendar-page #activity-calendar .activity-summary-grid {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 8px !important;
        margin: 0 0 10px !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-summary-item,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-summary-item,
    #practice-calendar-page #activity-calendar .activity-summary-item {
        min-height: 54px !important;
        padding: 8px 10px !important;
        border-radius: 9px !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-summary-item strong,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-summary-item strong,
    #practice-calendar-page #activity-calendar .activity-summary-item strong {
        font-size: 0.95rem !important;
        line-height: 1.05 !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-summary-item span,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-summary-item span,
    #practice-calendar-page #activity-calendar .activity-summary-item span {
        font-size: 0.62rem !important;
        line-height: 1.15 !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-grid,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-grid,
    #practice-calendar-page #activity-calendar .activity-grid {
        display: grid !important;
        grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
        gap: 8px !important;
        width: 100% !important;
        margin: 0 !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-day,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-day,
    #practice-calendar-page #activity-calendar .activity-day {
        min-height: 48px !important;
        padding: 6px 8px !important;
        border-radius: 9px !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-day-week,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-day-week,
    #practice-calendar-page #activity-calendar .activity-day-week {
        font-size: 0.56rem !important;
        line-height: 1 !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-day-number,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-day-number,
    #practice-calendar-page #activity-calendar .activity-day-number {
        font-size: 0.78rem !important;
        line-height: 1.05 !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-day-dot,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-day-dot,
    #practice-calendar-page #activity-calendar .activity-day-dot {
        width: 18px !important;
        height: 18px !important;
        min-width: 18px !important;
        font-size: 0.58rem !important;
        line-height: 18px !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-legend,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-legend,
    #practice-calendar-page #activity-calendar .activity-legend {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 8px !important;
        margin: 10px 0 0 !important;
        min-height: 28px !important;
        width: 100% !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-legend span,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-legend span,
    #practice-calendar-page #activity-calendar .activity-legend span {
        grid-column: 1 !important;
        justify-self: start !important;
        gap: 6px !important;
        font-size: 0.68rem !important;
        line-height: 1.15 !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-legend span i,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-legend span i,
    #practice-calendar-page #activity-calendar .activity-legend span i {
        width: 10px !important;
        height: 10px !important;
        min-width: 10px !important;
        border-radius: 3px !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-cta.compact,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-cta.compact,
    #practice-calendar-page #activity-calendar .activity-cta.compact {
        grid-column: 2 !important;
        justify-self: center !important;
        min-height: 28px !important;
        padding: 5px 8px !important;
        border-radius: 7px !important;
        gap: 5px !important;
        font-size: 0.62rem !important;
        line-height: 1.1 !important;
        font-weight: 900 !important;
        white-space: nowrap !important;
    }

    html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-cta.compact i,
    html body #mob-content #practice-calendar-page #activity-calendar .activity-cta.compact i,
    #practice-calendar-page #activity-calendar .activity-cta.compact i {
        margin-right: 0 !important;
        font-size: 0.66rem !important;
        line-height: 1 !important;
    }

    @media (max-width: 767px) {
        html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-summary-grid,
        html body #mob-content #practice-calendar-page #activity-calendar .activity-summary-grid,
        #practice-calendar-page #activity-calendar .activity-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 6px !important;
        }

        html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-grid,
        html body #mob-content #practice-calendar-page #activity-calendar .activity-grid,
        #practice-calendar-page #activity-calendar .activity-grid {
            gap: 5px !important;
        }

        html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-day,
        html body #mob-content #practice-calendar-page #activity-calendar .activity-day,
        #practice-calendar-page #activity-calendar .activity-day {
            min-height: 42px !important;
            padding: 5px 4px !important;
        }

        html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-day-week,
        html body #mob-content #practice-calendar-page #activity-calendar .activity-day-week,
        #practice-calendar-page #activity-calendar .activity-day-week {
            font-size: 0.48rem !important;
        }

        html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-day-number,
        html body #mob-content #practice-calendar-page #activity-calendar .activity-day-number,
        #practice-calendar-page #activity-calendar .activity-day-number {
            font-size: 0.68rem !important;
        }

        html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-legend,
        html body #mob-content #practice-calendar-page #activity-calendar .activity-legend,
        #practice-calendar-page #activity-calendar .activity-legend {
            grid-template-columns: minmax(0, 1fr) !important;
            justify-items: center !important;
        }

        html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-legend span,
        html body #dashboard .db-content #practice-calendar-page #activity-calendar .activity-cta.compact,
        html body #mob-content #practice-calendar-page #activity-calendar .activity-legend span,
        html body #mob-content #practice-calendar-page #activity-calendar .activity-cta.compact,
        #practice-calendar-page #activity-calendar .activity-legend span,
        #practice-calendar-page #activity-calendar .activity-cta.compact {
            grid-column: 1 !important;
            justify-self: center !important;
        }

        /* Mobile activity calendar footer action hidden; the top page action remains available. */
        html body #mob-content #practice-calendar-page #activity-calendar .activity-cta.compact,
        body.user-mobile-shell #mob-content #practice-calendar-page #activity-calendar .activity-cta.compact {
            display: none !important;
        }
    }

    /* Final night theme visibility: keep practice pages readable on dark surfaces. */
    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan .practice-plan-panel,
    .dm :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan .practice-plan-panel,
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-plan-page #personalized-practice-plan .practice-plan-panel,
    .dm #practice-plan-page #personalized-practice-plan .practice-plan-panel {
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(17, 24, 39, 0.96)) !important;
        border-color: rgba(148, 163, 184, 0.24) !important;
        box-shadow: 0 16px 34px rgba(2, 6, 23, 0.24) !important;
        color: #dbe5f3 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan .practice-plan-row,
    .dm :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan .practice-plan-row,
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-plan-page #personalized-practice-plan .practice-plan-row,
    .dm #practice-plan-page #personalized-practice-plan .practice-plan-row {
        background:
            radial-gradient(circle at 86% 16%, rgba(59, 130, 246, 0.16), transparent 34%),
            linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.88)) !important;
        border-color: rgba(148, 163, 184, 0.28) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05) !important;
        color: #f8fafc !important;
        opacity: 1 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-title, .practice-plan-title),
    .dm :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-title, .practice-plan-title),
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-title, .practice-plan-title),
    .dm #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-title, .practice-plan-title) {
        color: #f8fafc !important;
        -webkit-text-fill-color: #f8fafc !important;
        opacity: 1 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-text, .practice-plan-text, .practice-plan-tasks li),
    .dm :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-text, .practice-plan-text, .practice-plan-tasks li),
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-text, .practice-plan-text, .practice-plan-tasks li),
    .dm #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-text, .practice-plan-text, .practice-plan-tasks li) {
        color: #dbe5f3 !important;
        -webkit-text-fill-color: #dbe5f3 !important;
        opacity: 1 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan :is(.practice-plan-step, .practice-plan-link),
    .dm :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan :is(.practice-plan-step, .practice-plan-link),
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-plan-page #personalized-practice-plan :is(.practice-plan-step, .practice-plan-link),
    .dm #practice-plan-page #personalized-practice-plan :is(.practice-plan-step, .practice-plan-link) {
        color: #93c5fd !important;
        -webkit-text-fill-color: #93c5fd !important;
        opacity: 1 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan .practice-plan-step,
    .dm :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan .practice-plan-step,
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-plan-page #personalized-practice-plan .practice-plan-step,
    .dm #practice-plan-page #personalized-practice-plan .practice-plan-step {
        background: rgba(59, 130, 246, 0.16) !important;
        border-color: rgba(147, 197, 253, 0.22) !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan .practice-plan-pill,
    .dm :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan .practice-plan-pill,
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-plan-page #personalized-practice-plan .practice-plan-pill,
    .dm #practice-plan-page #personalized-practice-plan .practice-plan-pill {
        background: rgba(16, 185, 129, 0.18) !important;
        color: #86efac !important;
        -webkit-text-fill-color: #86efac !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-icon, .practice-plan-icon, .practice-plan-tasks li i),
    .dm :is(#dashboard .db-content, #mob-content) #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-icon, .practice-plan-icon, .practice-plan-tasks li i),
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-icon, .practice-plan-icon, .practice-plan-tasks li i),
    .dm #practice-plan-page #personalized-practice-plan :is(.practice-plan-heading-icon, .practice-plan-icon, .practice-plan-tasks li i) {
        background: rgba(16, 185, 129, 0.16) !important;
        border-color: rgba(110, 231, 183, 0.22) !important;
        color: #a7f3d0 !important;
        -webkit-text-fill-color: #a7f3d0 !important;
        opacity: 1 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar .activity-panel,
    .dm :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar .activity-panel,
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-calendar-page #activity-calendar .activity-panel,
    .dm #practice-calendar-page #activity-calendar .activity-panel {
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(17, 24, 39, 0.96)) !important;
        border-color: rgba(148, 163, 184, 0.24) !important;
        box-shadow: 0 16px 34px rgba(2, 6, 23, 0.24) !important;
        color: #dbe5f3 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar :is(.activity-summary-item, .activity-day),
    .dm :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar :is(.activity-summary-item, .activity-day),
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-calendar-page #activity-calendar :is(.activity-summary-item, .activity-day),
    .dm #practice-calendar-page #activity-calendar :is(.activity-summary-item, .activity-day) {
        background: rgba(30, 41, 59, 0.82) !important;
        border-color: rgba(148, 163, 184, 0.26) !important;
        color: #f8fafc !important;
        opacity: 1 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar .activity-day.active,
    .dm :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar .activity-day.active,
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-calendar-page #activity-calendar .activity-day.active,
    .dm #practice-calendar-page #activity-calendar .activity-day.active {
        background:
            linear-gradient(180deg, rgba(99, 102, 241, 0.26), rgba(20, 184, 166, 0.24)),
            rgba(30, 41, 59, 0.9) !important;
        border-color: rgba(96, 165, 250, 0.44) !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar .activity-day.active::before,
    .dm :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar .activity-day.active::before,
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-calendar-page #activity-calendar .activity-day.active::before,
    .dm #practice-calendar-page #activity-calendar .activity-day.active::before {
        background: linear-gradient(180deg, rgba(99, 102, 241, 0.28), rgba(20, 184, 166, 0.26)) !important;
        opacity: 1 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar .activity-day.today,
    .dm :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar .activity-day.today,
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-calendar-page #activity-calendar .activity-day.today,
    .dm #practice-calendar-page #activity-calendar .activity-day.today {
        border-color: rgba(96, 165, 250, 0.7) !important;
        box-shadow: 0 0 0 1px rgba(96, 165, 250, 0.22) !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar :is(.activity-title, .activity-summary-item strong, .activity-day-number),
    .dm :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar :is(.activity-title, .activity-summary-item strong, .activity-day-number),
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-calendar-page #activity-calendar :is(.activity-title, .activity-summary-item strong, .activity-day-number),
    .dm #practice-calendar-page #activity-calendar :is(.activity-title, .activity-summary-item strong, .activity-day-number) {
        color: #f8fafc !important;
        -webkit-text-fill-color: #f8fafc !important;
        opacity: 1 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar :is(.activity-subtitle, .activity-summary-item span, .activity-day-week, .activity-legend span),
    .dm :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar :is(.activity-subtitle, .activity-summary-item span, .activity-day-week, .activity-legend span),
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-calendar-page #activity-calendar :is(.activity-subtitle, .activity-summary-item span, .activity-day-week, .activity-legend span),
    .dm #practice-calendar-page #activity-calendar :is(.activity-subtitle, .activity-summary-item span, .activity-day-week, .activity-legend span) {
        color: #cbd5e1 !important;
        -webkit-text-fill-color: #cbd5e1 !important;
        opacity: 1 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar :is(.activity-heading-icon, .activity-legend span i),
    .dm :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar :is(.activity-heading-icon, .activity-legend span i),
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-calendar-page #activity-calendar :is(.activity-heading-icon, .activity-legend span i),
    .dm #practice-calendar-page #activity-calendar :is(.activity-heading-icon, .activity-legend span i) {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.24), rgba(20, 184, 166, 0.2)) !important;
        border-color: rgba(165, 180, 252, 0.22) !important;
        color: #c4b5fd !important;
        -webkit-text-fill-color: #c4b5fd !important;
        opacity: 1 !important;
    }

    :is(html:not(.lm), html[data-theme="dark"]) body :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar .activity-cta.compact,
    .dm :is(#dashboard .db-content, #mob-content) #practice-calendar-page #activity-calendar .activity-cta.compact,
    :is(html:not(.lm), html[data-theme="dark"]) body #practice-calendar-page #activity-calendar .activity-cta.compact,
    .dm #practice-calendar-page #activity-calendar .activity-cta.compact {
        background: rgba(37, 99, 235, 0.14) !important;
        border-color: rgba(96, 165, 250, 0.48) !important;
        color: #60a5fa !important;
        -webkit-text-fill-color: #60a5fa !important;
    }
</style>
