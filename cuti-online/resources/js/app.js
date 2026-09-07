const isWorkspaceFrame = window.self !== window.top;

if (isWorkspaceFrame) {
    let frameHeightUpdateQueued = false;

    const sendWorkspaceFrameHeight = () => {
        frameHeightUpdateQueued = false;

        const height = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);

        window.parent.postMessage({ type: 'simpeg-workspace-frame-height', height }, window.location.origin);
    };

    const scheduleWorkspaceFrameHeight = () => {
        if (frameHeightUpdateQueued) {
            return;
        }

        frameHeightUpdateQueued = true;
        window.requestAnimationFrame(sendWorkspaceFrameHeight);
    };

    if ('ResizeObserver' in window) {
        new ResizeObserver(scheduleWorkspaceFrameHeight).observe(document.body);
    }

    window.addEventListener('load', scheduleWorkspaceFrameHeight);
    window.addEventListener('pageshow', scheduleWorkspaceFrameHeight);
    scheduleWorkspaceFrameHeight();

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest?.('a[data-workspace-link]');

        if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) {
            return;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();
        window.parent.postMessage({
            type: 'simpeg-workspace-open',
            title: link.dataset.workspaceTitle ?? link.textContent.trim(),
            url: url.href,
        }, window.location.origin);
    });
}

const workspaceRoot = document.querySelector('[data-workspace-root]');

if (workspaceRoot && !isWorkspaceFrame) {
    const workspaceTablist = workspaceRoot.querySelector('[role="tablist"]');
    const workspacePanels = workspaceRoot.querySelector('[data-workspace-panels]');
    const initialTab = workspaceRoot.querySelector('[data-workspace-tab-id="initial"]');
    const initialPanel = workspaceRoot.querySelector('[data-workspace-static-panel]');
    const dashboardUrl = workspaceRoot.dataset.workspaceDashboardUrl;
    const workspaceLinks = Array.from(document.querySelectorAll('[data-workspace-link]'));
    const sidebarLinks = workspaceLinks.filter((link) => link.classList.contains('sidebar-link'));

    if (workspaceTablist && workspacePanels && initialTab && initialPanel) {
        const tabs = new Map();
        const tabIdsByUrl = new Map();
        const tabOrder = ['initial'];
        const workspaceUrlKey = (url) => {
            const parsedUrl = new URL(url, window.location.href);

            return `${parsedUrl.origin}${parsedUrl.pathname}`;
        };
        let activeTabId = 'initial';
        let nextTabIndex = 1;
        const initialUrlKey = workspaceUrlKey(initialTab.dataset.workspaceUrl ?? window.location.href);

        tabs.set('initial', {
            container: initialTab,
            panel: initialPanel,
            trigger: initialTab.querySelector('[data-workspace-tab-trigger]'),
            urlKey: initialUrlKey,
        });
        tabIdsByUrl.set(initialUrlKey, 'initial');

        const syncSidebarLinks = (urlKey) => {
            sidebarLinks.forEach((link) => {
                link.classList.toggle('sidebar-link-active', workspaceUrlKey(link.href) === urlKey);
            });
        };

        const activateWorkspaceTab = (tabId) => {
            const nextTab = tabs.get(tabId);

            if (!nextTab) {
                return;
            }

            activeTabId = tabId;

            tabs.forEach((tab, currentTabId) => {
                const isActive = currentTabId === tabId;

                tab.container.classList.toggle('workspace-tab-active', isActive);
                tab.trigger?.setAttribute('aria-selected', String(isActive));
                tab.trigger?.setAttribute('tabindex', isActive ? '0' : '-1');
                tab.panel.hidden = !isActive;
            });

            syncSidebarLinks(nextTab.urlKey);
        };

        const closeWorkspaceTab = (tabId) => {
            if (tabId === 'initial') {
                if (dashboardUrl) {
                    window.location.assign(dashboardUrl);
                }

                return;
            }

            const tab = tabs.get(tabId);

            if (!tab) {
                return;
            }

            const tabIndex = tabOrder.indexOf(tabId);
            const nextTabId = tabOrder[tabIndex + 1] ?? tabOrder[tabIndex - 1] ?? 'initial';
            const tabToFocusId = activeTabId === tabId ? nextTabId : activeTabId;

            tab.container.remove();
            tab.panel.remove();
            tabs.delete(tabId);
            tabIdsByUrl.delete(tab.urlKey);
            tabOrder.splice(tabIndex, 1);

            activateWorkspaceTab(tabToFocusId);
            tabs.get(tabToFocusId)?.trigger?.focus();
        };

        const createWorkspaceTab = (url, title) => {
            const urlKey = workspaceUrlKey(url);
            const existingTabId = tabIdsByUrl.get(urlKey);
            const existingTab = existingTabId ? tabs.get(existingTabId) : null;

            if (existingTab) {
                if (existingTab.frame) {
                    existingTab.frame.src = url;
                }

                activateWorkspaceTab(existingTabId);
                existingTab.trigger?.focus();
                return;
            }

            if (existingTabId) {
                tabIdsByUrl.delete(urlKey);
            }

            const tabId = `workspace-tab-${nextTabIndex}`;
            const panelId = `workspace-panel-${nextTabIndex}`;
            nextTabIndex += 1;

            const tabContainer = document.createElement('div');
            tabContainer.className = 'workspace-tab';
            tabContainer.dataset.workspaceTab = '';
            tabContainer.dataset.workspaceTabId = tabId;

            const trigger = document.createElement('button');
            trigger.className = 'workspace-tab-trigger';
            trigger.id = tabId;
            trigger.type = 'button';
            trigger.setAttribute('role', 'tab');
            trigger.setAttribute('aria-selected', 'false');
            trigger.setAttribute('aria-controls', panelId);
            trigger.setAttribute('tabindex', '-1');
            trigger.dataset.workspaceTabTrigger = '';
            trigger.dataset.workspaceTabId = tabId;
            trigger.title = title;

            const tabLabel = document.createElement('span');
            tabLabel.className = 'truncate';
            tabLabel.textContent = title;
            trigger.append(tabLabel);

            const closeButton = document.createElement('button');
            closeButton.className = 'workspace-tab-close';
            closeButton.type = 'button';
            closeButton.setAttribute('aria-label', `Tutup tab ${title}`);
            closeButton.title = 'Tutup tab';
            closeButton.dataset.workspaceTabClose = '';
            closeButton.dataset.workspaceTabId = tabId;

            const closeIcon = document.createElement('span');
            closeIcon.setAttribute('aria-hidden', 'true');
            closeIcon.className = 'text-lg leading-none';
            closeIcon.textContent = '×';
            closeButton.append(closeIcon);

            tabContainer.append(trigger, closeButton);
            workspaceTablist.append(tabContainer);

            const panel = document.createElement('section');
            panel.id = panelId;
            panel.hidden = true;
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', tabId);
            panel.dataset.workspacePanel = '';

            const frame = document.createElement('iframe');
            frame.className = 'workspace-frame';
            frame.src = url;
            frame.title = `${title} — ruang kerja`;
            frame.setAttribute('scrolling', 'auto');
            frame.dataset.workspaceFrame = '';
            panel.append(frame);
            workspacePanels.append(panel);

            tabs.set(tabId, {
                container: tabContainer,
                frame,
                panel,
                trigger,
                urlKey,
            });
            tabIdsByUrl.set(urlKey, tabId);
            tabOrder.push(tabId);

            activateWorkspaceTab(tabId);
        };

        workspaceLinks.forEach((link) => {
            link.addEventListener('click', (event) => {
                if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                const url = new URL(link.href, window.location.href);

                if (url.origin !== window.location.origin) {
                    return;
                }

                event.preventDefault();
                createWorkspaceTab(url.href, link.dataset.workspaceTitle ?? link.textContent.trim());
            });
        });

        workspaceTablist.addEventListener('click', (event) => {
            const closeButton = event.target.closest('[data-workspace-tab-close]');

            if (closeButton && workspaceTablist.contains(closeButton)) {
                closeWorkspaceTab(closeButton.dataset.workspaceTabId);
                return;
            }

            const trigger = event.target.closest('[data-workspace-tab-trigger]');

            if (trigger && workspaceTablist.contains(trigger)) {
                activateWorkspaceTab(trigger.dataset.workspaceTabId);
            }
        });

        workspaceTablist.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
                return;
            }

            const triggers = Array.from(workspaceTablist.querySelectorAll('[data-workspace-tab-trigger]'));
            const currentIndex = triggers.indexOf(document.activeElement);

            if (currentIndex === -1) {
                return;
            }

            let nextIndex = currentIndex;

            if (event.key === 'ArrowLeft') {
                nextIndex = currentIndex === 0 ? triggers.length - 1 : currentIndex - 1;
            }

            if (event.key === 'ArrowRight') {
                nextIndex = currentIndex === triggers.length - 1 ? 0 : currentIndex + 1;
            }

            if (event.key === 'Home') {
                nextIndex = 0;
            }

            if (event.key === 'End') {
                nextIndex = triggers.length - 1;
            }

            event.preventDefault();
            activateWorkspaceTab(triggers[nextIndex].dataset.workspaceTabId);
            triggers[nextIndex].focus();
        });

        window.addEventListener('message', (event) => {
            if (event.origin !== window.location.origin) {
                return;
            }

            const frame = Array.from(workspacePanels.querySelectorAll('[data-workspace-frame]'))
                .find((workspaceFrame) => workspaceFrame.contentWindow === event.source);

            if (event.data?.type === 'simpeg-workspace-open') {
                if (!frame || typeof event.data.url !== 'string') {
                    return;
                }

                let url;

                try {
                    url = new URL(event.data.url, window.location.href);
                } catch {
                    return;
                }

                if (url.origin !== window.location.origin) {
                    return;
                }

                const title = typeof event.data.title === 'string' && event.data.title.trim()
                    ? event.data.title.trim()
                    : url.pathname;

                createWorkspaceTab(url.href, title);
                return;
            }

            if (event.data?.type !== 'simpeg-workspace-frame-height') {
                return;
            }

            const height = Number(event.data.height);

            if (!frame || !Number.isFinite(height) || height <= 0) {
                return;
            }

            const viewportHeight = window.innerHeight;
            const cappedHeight = Math.max(608, Math.min(viewportHeight, Math.ceil(height)));

            frame.style.height = `${cappedHeight}px`;
        });
    }
}

const leaveForm = document.querySelector('[data-leave-form]');

if (leaveForm) {
    const startDate = leaveForm.querySelector('[name="start_date"]');
    const endDate = leaveForm.querySelector('[name="end_date"]');
    const leaveType = leaveForm.querySelector('[name="leave_type_id"]');
    const durationUnit = leaveForm.querySelector('[name="duration_unit"]');
    const output = leaveForm.querySelector('[data-duration-output]');
    const employeeSelect = leaveForm.querySelector('[name="employee_id"]');
    const employeeCombobox = leaveForm.querySelector('[data-employee-combobox]');
    const employeeSearch = leaveForm.querySelector('[data-employee-search]');
    const employeeSearchPanel = leaveForm.querySelector('[data-employee-search-panel]');
    const employeeSearchResult = leaveForm.querySelector('[data-employee-search-result]');
    const supervisorSelect = leaveForm.querySelector('[name="supervisor_employee_id"]');
    const supervisorCombobox = leaveForm.querySelector('[data-supervisor-combobox]');
    const supervisorSearch = leaveForm.querySelector('[data-supervisor-search]');
    const supervisorSearchPanel = leaveForm.querySelector('[data-supervisor-search-panel]');
    const supervisorSearchResult = leaveForm.querySelector('[data-supervisor-search-result]');
    const authorizedOfficialDataElement = document.querySelector('#authorized-official');
    const authorizedOfficial = authorizedOfficialDataElement ? JSON.parse(authorizedOfficialDataElement.textContent) : null;
    const sekdaDataElement = document.querySelector('#sekda-official');
    const sekdaOfficial = sekdaDataElement ? JSON.parse(sekdaDataElement.textContent) : null;
    const walikotaDataElement = document.querySelector('#walikota-official');
    const walikotaOfficial = walikotaDataElement ? JSON.parse(walikotaDataElement.textContent) : null;
    const camatToggle = leaveForm.querySelector('[data-camat-toggle]');
    const plhToggle = leaveForm.querySelector('[data-plh-toggle]');
    const plhSelect = leaveForm.querySelector('[name="plh_employee_id"]');
    const supervisorHeading = leaveForm.querySelector('[data-supervisor-heading]');
    const officialHeading = leaveForm.querySelector('[data-official-heading]');
    const officialHint = leaveForm.querySelector('[data-official-hint]');
    const submitButton = leaveForm.querySelector('[data-submit-leave-form]');
    const submitLabel = submitButton?.textContent;
    let isSubmitting = false;

    const dateRangePicker = leaveForm.querySelector('[data-date-range-picker]');
    const dateRangeGrid = dateRangePicker?.querySelector('[data-date-range-grid]');
    const dateRangeMonth = dateRangePicker?.querySelector('[data-date-range-month]');
    const dateRangeSummary = dateRangePicker?.querySelector('[data-date-range-summary]');
    const dateRangeSelectionHelp = dateRangePicker?.querySelector('[data-date-range-selection-help]');
    const dateRangePrevious = dateRangePicker?.querySelector('[data-date-range-prev]');
    const dateRangeNext = dateRangePicker?.querySelector('[data-date-range-next]');
    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const shortMonthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const padDatePart = (value) => String(value).padStart(2, '0');
    const dateToValue = (date) => `${date.getFullYear()}-${padDatePart(date.getMonth() + 1)}-${padDatePart(date.getDate())}`;
    const parseDateValue = (value) => {
        if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return null;
        }

        const [year, month, day] = value.split('-').map(Number);
        const date = new Date(year, month - 1, day);

        return Number.isInteger(year)
            && Number.isInteger(month)
            && Number.isInteger(day)
            && date.getFullYear() === year
            && date.getMonth() === month - 1
            && date.getDate() === day
            ? date
            : null;
    };
    const formatLongDate = (date) => `${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;
    const formatShortDate = (date) => `${date.getDate()} ${shortMonthNames[date.getMonth()]} ${date.getFullYear()}`;
    const isWeekend = (date) => date.getDay() === 0 || date.getDay() === 6;
    const usesWorkingDaySelection = () => leaveType?.options[leaveType.selectedIndex]?.dataset.code === 'annual';
    const selectedDateValuesInOrder = () => Array.from(selectedDateValues).sort();
    const buildSelectedDateValues = (start, end) => {
        const values = new Set();

        if (!start || !end || dateToValue(end) < dateToValue(start)) {
            return values;
        }

        const cursor = new Date(start);

        while (dateToValue(cursor) <= dateToValue(end)) {
            if (!usesWorkingDaySelection() || !isWeekend(cursor)) {
                values.add(dateToValue(cursor));
            }

            cursor.setDate(cursor.getDate() + 1);
        }

        return values;
    };
    const initialStartDate = parseDateValue(startDate?.value);
    const initialEndDate = parseDateValue(endDate?.value) ?? initialStartDate;
    const selectedDateValues = buildSelectedDateValues(initialStartDate, initialEndDate);
    let calendarDate = new Date((initialStartDate ?? new Date()).getFullYear(), (initialStartDate ?? new Date()).getMonth(), 1);
    let selectionUsesWorkingDays = usesWorkingDaySelection();

    const selectedDateBounds = () => {
        const values = selectedDateValuesInOrder();

        return {
            values,
            start: values[0] ? parseDateValue(values[0]) : null,
            end: values.length > 0 ? parseDateValue(values[values.length - 1]) : null,
        };
    };
    const adjacentSelectableDate = (date, direction) => {
        const adjacent = new Date(date);

        do {
            adjacent.setDate(adjacent.getDate() + direction);
        } while (usesWorkingDaySelection() && isWeekend(adjacent));

        return adjacent;
    };
    const updateDateRangeSelectionHelp = () => {
        if (!dateRangeSelectionHelp) {
            return;
        }

        dateRangeSelectionHelp.textContent = usesWorkingDaySelection()
            ? 'Untuk Cuti Tahunan, Sabtu dan Minggu tidak dapat dipilih. Klik ulang hari pertama atau terakhir untuk membatalkannya.'
            : 'Pilih tanggal satu per satu tanpa melewatkan hari. Klik ulang tanggal pertama atau terakhir untuk membatalkannya.';
    };
    const renderDateRangeSummary = () => {
        if (!dateRangeSummary) {
            return;
        }

        const { values, start, end } = selectedDateBounds();

        const selectedDayLabel = usesWorkingDaySelection() ? 'hari kerja' : 'hari';

        if (!start || !end) {
            dateRangeSummary.textContent = 'Pilih hari pertama cuti.';
        } else if (values.length === 1) {
            dateRangeSummary.textContent = `${formatShortDate(start)} dipilih. Pilih ${selectedDayLabel} berikutnya yang berurutan.`;
        } else {
            dateRangeSummary.textContent = `${values.length} ${selectedDayLabel} dipilih: ${formatShortDate(start)} — ${formatShortDate(end)}`;
        }
    };
    const renderDateRangePicker = () => {
        if (!dateRangeGrid || !dateRangeMonth) {
            return;
        }

        const year = calendarDate.getFullYear();
        const month = calendarDate.getMonth();
        const firstWeekday = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const todayValue = dateToValue(new Date());
        const { values, start, end } = selectedDateBounds();
        const selectedValues = new Set(values);
        const startValue = start ? dateToValue(start) : '';
        const endValue = end ? dateToValue(end) : '';

        dateRangeMonth.textContent = `${monthNames[month]} ${year}`;
        dateRangeGrid.replaceChildren();

        for (let index = 0; index < firstWeekday; index++) {
            const emptyCell = document.createElement('span');

            emptyCell.className = 'date-range-picker-day is-empty';
            emptyCell.setAttribute('aria-hidden', 'true');
            dateRangeGrid.append(emptyCell);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const value = dateToValue(date);
            const button = document.createElement('button');
            const isWeekendDay = usesWorkingDaySelection() && isWeekend(date);
            const isSelected = selectedValues.has(value);
            const isStart = value === startValue;
            const isEnd = value === endValue;

            button.className = 'date-range-picker-day';
            button.type = 'button';
            button.dataset.dateRangeDay = '';
            button.dataset.dateValue = value;
            button.disabled = isWeekendDay;
            button.setAttribute('role', 'gridcell');
            button.setAttribute('aria-label', formatLongDate(date));
            button.setAttribute('aria-selected', String(isSelected));
            button.textContent = String(day);

            if (value === todayValue) {
                button.classList.add('is-today');
                button.setAttribute('aria-current', 'date');
            }

            if (isWeekendDay) {
                button.classList.add('is-weekend');
                button.setAttribute('aria-disabled', 'true');
                button.title = 'Sabtu dan Minggu tidak dapat dipilih untuk Cuti Tahunan.';
            }

            if (isSelected) {
                button.classList.add('is-selected');
            }

            if (isStart) {
                button.classList.add('is-start');
            }

            if (isEnd) {
                button.classList.add('is-end');
            }

            dateRangeGrid.append(button);
        }

        const cellCount = firstWeekday + daysInMonth;

        for (let index = cellCount; index % 7 !== 0; index++) {
            const emptyCell = document.createElement('span');

            emptyCell.className = 'date-range-picker-day is-empty';
            emptyCell.setAttribute('aria-hidden', 'true');
            dateRangeGrid.append(emptyCell);
        }

        updateDateRangeSelectionHelp();
        renderDateRangeSummary();
    };
    const syncDateRangeInputs = () => {
        const { values } = selectedDateBounds();

        startDate.value = values[0] ?? '';
        endDate.value = values.length > 0 ? values[values.length - 1] : '';
    };
    const selectDateRangeDay = (value) => {
        const selectedDate = parseDateValue(value);
        const { values, start, end } = selectedDateBounds();

        if (!selectedDate || (usesWorkingDaySelection() && isWeekend(selectedDate))) {
            return;
        }

        if (selectedDateValues.has(value)) {
            if (values.length === 1) {
                selectedDateValues.clear();
            } else if (value === values[0] || value === values[values.length - 1]) {
                selectedDateValues.delete(value);
            } else {
                dateRangePicker?.classList.add('date-range-picker-invalid');

                if (dateRangeSummary) {
                    dateRangeSummary.textContent = 'Hanya hari pertama atau terakhir yang dapat dibatalkan agar pilihan tidak terputus.';
                }

                return;
            }
        } else if (!start || !end) {
            selectedDateValues.add(value);
        } else {
            const previousValue = dateToValue(adjacentSelectableDate(start, -1));
            const nextValue = dateToValue(adjacentSelectableDate(end, 1));

            if (value !== previousValue && value !== nextValue) {
                dateRangePicker?.classList.add('date-range-picker-invalid');

                if (dateRangeSummary) {
                    dateRangeSummary.textContent = `Pilihan harus berurutan. Pilih ${formatShortDate(parseDateValue(previousValue))} atau ${formatShortDate(parseDateValue(nextValue))}.`;
                }

                return;
            }

            selectedDateValues.add(value);
        }

        syncDateRangeInputs();
        dateRangePicker?.classList.remove('date-range-picker-invalid');
        renderDateRangePicker();
        calculatePreview();
    };

    dateRangeGrid?.addEventListener('click', (event) => {
        const dayButton = event.target.closest('[data-date-range-day]');

        if (dayButton) {
            selectDateRangeDay(dayButton.dataset.dateValue);
        }
    });
    dateRangePrevious?.addEventListener('click', () => {
        calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() - 1, 1);
        renderDateRangePicker();
    });
    dateRangeNext?.addEventListener('click', () => {
        calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() + 1, 1);
        renderDateRangePicker();
    });
    leaveType?.addEventListener('change', () => {
        const nextSelectionUsesWorkingDays = usesWorkingDaySelection();

        if (nextSelectionUsesWorkingDays !== selectionUsesWorkingDays) {
            const { start, end } = selectedDateBounds();

            selectedDateValues.clear();
            buildSelectedDateValues(start, end).forEach((value) => selectedDateValues.add(value));
            syncDateRangeInputs();
        }

        selectionUsesWorkingDays = nextSelectionUsesWorkingDays;
        dateRangePicker?.classList.remove('date-range-picker-invalid');
        renderDateRangePicker();
        calculatePreview();
    });
    syncDateRangeInputs();
    renderDateRangePicker();

    const calculatePreview = () => {
        if (!startDate.value || !endDate.value) {
            output.textContent = 'Pilih tanggal mulai dan selesai untuk melihat perkiraan durasi.';
            return;
        }

        const start = new Date(`${startDate.value}T00:00:00`);
        const end = new Date(`${endDate.value}T00:00:00`);

        if (end < start) {
            output.textContent = 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.';
            return;
        }

        const selectedType = leaveType.options[leaveType.selectedIndex];
        const isAnnualLeave = selectedType?.dataset.code === 'annual';
        const unit = durationUnit.value;
        let duration = 1;

        if (unit === 'day') {
            if (isAnnualLeave) {
                const cursor = new Date(start);
                duration = 0;

                while (cursor <= end) {
                    const day = cursor.getDay();

                    if (day !== 0 && day !== 6) {
                        duration += 1;
                    }

                    cursor.setDate(cursor.getDate() + 1);
                }
            } else {
                duration = Math.floor((end - start) / 86400000) + 1;
            }
        } else if (unit === 'month') {
            duration = Math.max(1, (end.getFullYear() - start.getFullYear()) * 12 + end.getMonth() - start.getMonth());
        } else {
            duration = Math.max(1, end.getFullYear() - start.getFullYear());
        }

        const label = unit === 'day' ? 'hari' : unit === 'month' ? 'bulan' : 'tahun';
        const qualifier = isAnnualLeave && unit === 'day' ? ' kerja (belum termasuk hari libur khusus)' : '';

        output.textContent = `Perkiraan: ${duration} ${label}${qualifier}.`;
    };

    [startDate, endDate, leaveType, durationUnit].forEach((element) => {
        element.addEventListener('change', calculatePreview);
    });
    calculatePreview();

    if (employeeCombobox && employeeSearch && employeeSelect && employeeSearchPanel) {
        const employeeOptions = Array.from(employeeCombobox.querySelectorAll('[data-employee-option]'));
        const normaliseEmployeeSearchValue = (value) => value
            .toLocaleLowerCase('id-ID')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9*]+/g, ' ')
            .trim();
        const escapeRegularExpression = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const employeeSearchMatcher = (query) => {
            const expression = normaliseEmployeeSearchValue(query)
                .split('*')
                .flatMap((part) => part.split(' '))
                .filter(Boolean)
                .map(escapeRegularExpression)
                .join('.*');

            return expression ? new RegExp(expression, 'i') : null;
        };
        let activeEmployeeOptionIndex = -1;
        const visibleEmployeeOptions = () => employeeOptions.filter((option) => !option.hidden);
        const clearActiveEmployeeOption = () => {
            activeEmployeeOptionIndex = -1;
            employeeOptions.forEach((option) => {
                option.classList.remove('bg-sky-50', 'text-sky-950');
            });
            employeeSearch.removeAttribute('aria-activedescendant');
        };
        const updateEmployeeSearch = () => {
            const matcher = employeeSearchMatcher(employeeSearch.value);
            let resultCount = 0;

            employeeOptions.forEach((option) => {
                const matches = !matcher || matcher.test(normaliseEmployeeSearchValue(option.dataset.employeeLabel));
                const listItem = option.closest('li');
                const index = option.querySelector('[data-employee-option-index]');

                option.hidden = !matches;
                listItem.hidden = !matches;

                if (matches) {
                    resultCount += 1;
                    index.textContent = `${resultCount}.`;
                }
            });

            const hasQuery = employeeSearch.value.trim() !== '';
            clearActiveEmployeeOption();

            if (!employeeSearchResult) {
                return;
            }

            if (!hasQuery) {
                employeeSearchResult.textContent = `${employeeOptions.length} pegawai tersedia.`;
                return;
            }

            employeeSearchResult.textContent = resultCount === 0
                ? 'Tidak ada pegawai yang cocok.'
                : `${resultCount} pegawai ditemukan.`;
        };

        const openEmployeeSearchPanel = () => {
            employeeSearchPanel.hidden = false;
            employeeSearch.setAttribute('aria-expanded', 'true');
        };
        const closeEmployeeSearchPanel = () => {
            employeeSearchPanel.hidden = true;
            employeeSearch.setAttribute('aria-expanded', 'false');
            clearActiveEmployeeOption();
        };
        const chooseEmployee = (option) => {
            employeeSelect.value = option.dataset.employeeId;
            employeeSearch.value = option.dataset.employeeLabel;
            employeeSearch.setCustomValidity('');
            updateEmployeeSearch();
            closeEmployeeSearchPanel();
            employeeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        };
        const activateEmployeeOption = (optionIndex) => {
            const visibleOptions = visibleEmployeeOptions();

            if (visibleOptions.length === 0) {
                return;
            }

            activeEmployeeOptionIndex = (optionIndex + visibleOptions.length) % visibleOptions.length;
            const activeOption = visibleOptions[activeEmployeeOptionIndex];

            employeeOptions.forEach((option) => {
                const isActive = option === activeOption;

                option.classList.toggle('bg-sky-50', isActive);
                option.classList.toggle('text-sky-950', isActive);
            });
            employeeSearch.setAttribute('aria-activedescendant', activeOption.id);
            activeOption.scrollIntoView({ block: 'nearest' });
        };

        employeeSearch.addEventListener('focus', () => {
            updateEmployeeSearch();
            openEmployeeSearchPanel();

            if (employeeSelect.value) {
                employeeSearch.select();
            }
        });
        employeeSearch.addEventListener('input', () => {
            if (employeeSelect.value) {
                employeeSelect.value = '';
                employeeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            employeeSearch.setCustomValidity('');
            updateEmployeeSearch();
            openEmployeeSearchPanel();
        });
        employeeSearch.addEventListener('keydown', (event) => {
            const visibleOptions = visibleEmployeeOptions();

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                openEmployeeSearchPanel();
                activateEmployeeOption(activeEmployeeOptionIndex + 1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                openEmployeeSearchPanel();
                activateEmployeeOption(activeEmployeeOptionIndex - 1);
                return;
            }

            if (event.key === 'Enter' && (activeEmployeeOptionIndex >= 0 || visibleOptions.length === 1)) {
                event.preventDefault();
                chooseEmployee(visibleOptions[activeEmployeeOptionIndex >= 0 ? activeEmployeeOptionIndex : 0]);
                return;
            }

            if (event.key === 'Escape') {
                closeEmployeeSearchPanel();
            }

            if (event.key === 'Tab') {
                closeEmployeeSearchPanel();
            }
        });
        employeeOptions.forEach((option) => {
            option.addEventListener('click', () => chooseEmployee(option));
        });
        document.addEventListener('click', (event) => {
            if (!employeeCombobox.contains(event.target)) {
                closeEmployeeSearchPanel();
            }
        });
        leaveForm.addEventListener('submit', (event) => {
            if (employeeSelect.value) {
                return;
            }

            event.preventDefault();
            employeeSearch.setCustomValidity('Pilih pegawai dari daftar.');
            employeeSearch.reportValidity();
            openEmployeeSearchPanel();
        });
        updateEmployeeSearch();
    }

    let updateSupervisorSearch = () => {};
    let clearSupervisorSelection = () => {};

    if (supervisorCombobox && supervisorSearch && supervisorSelect && supervisorSearchPanel) {
        const supervisorOptions = Array.from(supervisorCombobox.querySelectorAll('[data-supervisor-option]'));
        const normaliseSupervisorSearchValue = (value) => value
            .toLocaleLowerCase('id-ID')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9*]+/g, ' ')
            .trim();
        const escapeSupervisorRegularExpression = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const supervisorSearchMatcher = (query) => {
            const expression = normaliseSupervisorSearchValue(query)
                .split('*')
                .flatMap((part) => part.split(' '))
                .filter(Boolean)
                .map(escapeSupervisorRegularExpression)
                .join('.*');

            return expression ? new RegExp(expression, 'i') : null;
        };
        let activeSupervisorOptionIndex = -1;
        const visibleSupervisorOptions = () => supervisorOptions.filter((option) => !option.hidden);
        const clearActiveSupervisorOption = () => {
            activeSupervisorOptionIndex = -1;
            supervisorOptions.forEach((option) => {
                option.classList.remove('bg-sky-50', 'text-sky-950');
            });
            supervisorSearch.removeAttribute('aria-activedescendant');
        };
        const refreshSupervisorOptions = () => {
            const matcher = supervisorSearchMatcher(supervisorSearch.value);
            const selectedEmployeeId = employeeSelect?.value;
            let resultCount = 0;

            supervisorOptions.forEach((option) => {
                const isCurrentEmployee = option.dataset.comboboxId === selectedEmployeeId;
                const matches = !isCurrentEmployee && (!matcher || matcher.test(normaliseSupervisorSearchValue(option.dataset.comboboxLabel)));
                const listItem = option.closest('li');
                const index = option.querySelector('[data-combobox-option-index]');

                option.hidden = !matches;
                listItem.hidden = !matches;

                if (matches) {
                    resultCount += 1;
                    index.textContent = `${resultCount}.`;
                }
            });

            const hasQuery = supervisorSearch.value.trim() !== '';
            clearActiveSupervisorOption();

            if (!supervisorSearchResult) {
                return;
            }

            if (!hasQuery) {
                supervisorSearchResult.textContent = `${resultCount} pegawai tersedia.`;
                return;
            }

            supervisorSearchResult.textContent = resultCount === 0
                ? 'Tidak ada pegawai yang cocok.'
                : `${resultCount} pegawai ditemukan.`;
        };
        const openSupervisorSearchPanel = () => {
            supervisorSearchPanel.hidden = false;
            supervisorSearch.setAttribute('aria-expanded', 'true');
        };
        const closeSupervisorSearchPanel = () => {
            supervisorSearchPanel.hidden = true;
            supervisorSearch.setAttribute('aria-expanded', 'false');
            clearActiveSupervisorOption();
        };
        const chooseSupervisor = (option) => {
            const selectedId = option.dataset.comboboxId;

            if (plhToggle?.checked && plhSelect?.value === selectedId) {
                plhSelect.value = '';
                const plhSearchInput = plhSearch ?? leaveForm.querySelector('[data-plh-search]');
                if (plhSearchInput) {
                    plhSearchInput.value = '';
                }

                fillField('official_nip', '');
                fillField('official_position_title', '');
            }

            supervisorSelect.value = selectedId;
            supervisorSearch.value = option.dataset.comboboxLabel;
            supervisorSearch.setCustomValidity('');
            refreshSupervisorOptions();
            closeSupervisorSearchPanel();
            supervisorSelect.dispatchEvent(new Event('change', { bubbles: true }));
        };
        const activateSupervisorOption = (optionIndex) => {
            const visibleOptions = visibleSupervisorOptions();

            if (visibleOptions.length === 0) {
                return;
            }

            activeSupervisorOptionIndex = (optionIndex + visibleOptions.length) % visibleOptions.length;
            const activeOption = visibleOptions[activeSupervisorOptionIndex];

            supervisorOptions.forEach((option) => {
                const isActive = option === activeOption;

                option.classList.toggle('bg-sky-50', isActive);
                option.classList.toggle('text-sky-950', isActive);
            });
            supervisorSearch.setAttribute('aria-activedescendant', activeOption.id);
            activeOption.scrollIntoView({ block: 'nearest' });
        };

        updateSupervisorSearch = refreshSupervisorOptions;
        clearSupervisorSelection = () => {
            const hadSelection = Boolean(supervisorSelect.value || supervisorSearch.value);

            supervisorSelect.value = '';
            supervisorSearch.value = '';
            supervisorSearch.setCustomValidity('');
            refreshSupervisorOptions();
            closeSupervisorSearchPanel();

            if (hadSelection) {
                supervisorSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        supervisorSearch.addEventListener('focus', () => {
            if (camatToggle?.checked) {
                supervisorSearch.blur();
                return;
            }

            refreshSupervisorOptions();
            openSupervisorSearchPanel();

            if (supervisorSelect.value) {
                supervisorSearch.select();
            }
        });
        supervisorSearch.addEventListener('input', () => {
            if (camatToggle?.checked) {
                return;
            }

            if (supervisorSelect.value) {
                supervisorSelect.value = '';
                supervisorSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            supervisorSearch.setCustomValidity('');
            refreshSupervisorOptions();
            openSupervisorSearchPanel();
        });
        supervisorSearch.addEventListener('keydown', (event) => {
            const visibleOptions = visibleSupervisorOptions();

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                openSupervisorSearchPanel();
                activateSupervisorOption(activeSupervisorOptionIndex + 1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                openSupervisorSearchPanel();
                activateSupervisorOption(activeSupervisorOptionIndex - 1);
                return;
            }

            if (event.key === 'Enter' && (activeSupervisorOptionIndex >= 0 || visibleOptions.length === 1)) {
                event.preventDefault();
                chooseSupervisor(visibleOptions[activeSupervisorOptionIndex >= 0 ? activeSupervisorOptionIndex : 0]);
                return;
            }

            if (event.key === 'Escape') {
                closeSupervisorSearchPanel();
            }

            if (event.key === 'Tab') {
                closeSupervisorSearchPanel();
            }
        });
        supervisorOptions.forEach((option) => {
            option.addEventListener('click', () => chooseSupervisor(option));
        });
        document.addEventListener('click', (event) => {
            if (!supervisorCombobox.contains(event.target)) {
                closeSupervisorSearchPanel();
            }
        });
        leaveForm.addEventListener('submit', (event) => {
            if (event.defaultPrevented || supervisorSelect.value) {
                return;
            }

            event.preventDefault();
            supervisorSearch.setCustomValidity('Pilih atasan langsung dari daftar.');
            supervisorSearch.reportValidity();
            openSupervisorSearchPanel();
        });
        refreshSupervisorOptions();
    }

    const plhCombobox = leaveForm.querySelector('[data-plh-combobox]');
    const plhSearch = leaveForm.querySelector('[data-plh-search]');
    const plhSearchPanel = leaveForm.querySelector('[data-plh-search-panel]');
    const plhSearchResult = leaveForm.querySelector('[data-plh-search-result]');

    if (plhCombobox && plhSearch && plhSelect && plhSearchPanel) {
        const plhOptions = Array.from(plhCombobox.querySelectorAll('[data-plh-option]'));
        const normalisePlhSearchValue = (value) => value
            .toLocaleLowerCase('id-ID')
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9*]+/g, ' ')
            .trim();
        const escapePlhRegularExpression = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const plhSearchMatcher = (query) => {
            const expression = normalisePlhSearchValue(query)
                .split('*')
                .flatMap((part) => part.split(' '))
                .filter(Boolean)
                .map(escapePlhRegularExpression)
                .join('.*');

            return expression ? new RegExp(expression, 'i') : null;
        };
        let activePlhOptionIndex = -1;
        const visiblePlhOptions = () => plhOptions.filter((option) => !option.hidden);
        const clearActivePlhOption = () => {
            activePlhOptionIndex = -1;
            plhOptions.forEach((option) => {
                option.classList.remove('bg-sky-50', 'text-sky-950');
            });
            plhSearch.removeAttribute('aria-activedescendant');
        };
        const refreshPlhOptions = () => {
            const matcher = plhSearchMatcher(plhSearch.value);
            const selectedEmployeeId = employeeSelect?.value;
            let resultCount = 0;

            plhOptions.forEach((option) => {
                const isCurrentEmployee = option.dataset.comboboxId === selectedEmployeeId;
                const matches = !isCurrentEmployee && (!matcher || matcher.test(normalisePlhSearchValue(option.dataset.comboboxLabel)));
                const listItem = option.closest('li');
                const index = option.querySelector('[data-plh-option-index]');

                option.hidden = !matches;
                listItem.hidden = !matches;

                if (matches) {
                    resultCount += 1;
                    index.textContent = `${resultCount}.`;
                }
            });

            const hasQuery = plhSearch.value.trim() !== '';
            clearActivePlhOption();

            if (!plhSearchResult) {
                return;
            }

            if (!hasQuery) {
                plhSearchResult.textContent = `${resultCount} pegawai tersedia.`;
                return;
            }

            plhSearchResult.textContent = resultCount === 0
                ? 'Tidak ada pegawai yang cocok.'
                : `${resultCount} pegawai ditemukan.`;
        };
        const openPlhSearchPanel = () => {
            plhSearchPanel.hidden = false;
            plhSearch.setAttribute('aria-expanded', 'true');
        };
        const closePlhSearchPanel = () => {
            plhSearchPanel.hidden = true;
            plhSearch.setAttribute('aria-expanded', 'false');
            clearActivePlhOption();
        };
        const choosePlh = (option) => {
            if (supervisorSelect?.value === option.dataset.comboboxId) {
                closePlhSearchPanel();
                plhSearch.setCustomValidity('Atasan langsung tidak boleh menjadi PLH. Pilih pegawai lain.');
                plhSearch.reportValidity();
                refreshPlhOptions();
                return;
            }

            const fullName = option.dataset.fullName ?? '';
            const nip = option.dataset.nip ?? '';
            const positionTitle = option.dataset.positionTitle ?? '';

            plhSelect.value = option.dataset.comboboxId;
            plhSearch.value = fullName;
            plhSearch.setCustomValidity('');

            refreshPlhOptions();
            closePlhSearchPanel();

            fillField('official_nip', nip);
            fillField('official_position_title', positionTitle);
            officialHeading.textContent = 'Pejabat berwenang (PLH)';
        };
        const activatePlhOption = (optionIndex) => {
            const visibleOptions = visiblePlhOptions();

            if (visibleOptions.length === 0) {
                return;
            }

            activePlhOptionIndex = (optionIndex + visibleOptions.length) % visibleOptions.length;
            const activeOption = visibleOptions[activePlhOptionIndex];

            plhOptions.forEach((option) => {
                const isActive = option === activeOption;

                option.classList.toggle('bg-sky-50', isActive);
                option.classList.toggle('text-sky-950', isActive);
            });
            plhSearch.setAttribute('aria-activedescendant', activeOption.id);
            activeOption.scrollIntoView({ block: 'nearest' });
        };

        plhSearch.addEventListener('focus', () => {
            refreshPlhOptions();
            openPlhSearchPanel();

            if (plhSelect.value) {
                plhSearch.select();
            }
        });
        plhSearch.addEventListener('input', () => {
            if (plhSelect.value) {
                plhSelect.value = '';
            }

            plhSearch.setCustomValidity('');
            refreshPlhOptions();
            openPlhSearchPanel();
        });
        plhSearch.addEventListener('keydown', (event) => {
            const visibleOptions = visiblePlhOptions();

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                openPlhSearchPanel();
                activatePlhOption(activePlhOptionIndex + 1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                openPlhSearchPanel();
                activatePlhOption(activePlhOptionIndex - 1);
                return;
            }

            if (event.key === 'Enter' && (activePlhOptionIndex >= 0 || visibleOptions.length === 1)) {
                event.preventDefault();
                choosePlh(visibleOptions[activePlhOptionIndex >= 0 ? activePlhOptionIndex : 0]);
                return;
            }

            if (event.key === 'Escape') {
                closePlhSearchPanel();
            }

            if (event.key === 'Tab') {
                closePlhSearchPanel();
            }
        });
        plhOptions.forEach((option) => {
            option.addEventListener('click', () => choosePlh(option));
        });
        document.addEventListener('click', (event) => {
            if (!plhCombobox.contains(event.target)) {
                closePlhSearchPanel();
            }
        });
        leaveForm.addEventListener('submit', (event) => {
            if (!plhToggle?.checked) {
                return;
            }

            if (event.defaultPrevented || plhSelect.value) {
                return;
            }

            event.preventDefault();
            plhSearch.setCustomValidity('Pilih PLH dari daftar.');
            plhSearch.reportValidity();
            openPlhSearchPanel();
        });
        refreshPlhOptions();
    }

    const fillField = (name, value) => {
        const input = leaveForm.querySelector(`[name="${name}"]`);

        if (input) {
            input.value = value ?? '';
        }
    };

    const fillOfficial = (prefix, official, onlyIfEmpty = false) => {
        if (!official) {
            return;
        }

        [
            ['name', official.full_name],
            ['nip', official.nip],
            ['position_title', official.position_title],
        ].forEach(([field, value]) => {
            const input = leaveForm.querySelector(`[name="${prefix}_${field}"]`);

            if (!onlyIfEmpty || !input.value) {
                input.value = value ?? '';
            }
        });
    };

    const selectedSupervisor = () => {
        if (!supervisorSelect?.value) {
            return null;
        }

        const selectedOption = Array.from(leaveForm.querySelectorAll('[data-supervisor-option]'))
            .find((option) => option.dataset.comboboxId === supervisorSelect.value);

        return {
            full_name: selectedOption?.dataset.fullName ?? '',
            nip: selectedOption?.dataset.nip ?? '',
            position_title: selectedOption?.dataset.positionTitle ?? '',
        };
    };

    const fillSupervisorDetails = () => {
        const supervisor = selectedSupervisor();

        [
            ['name', supervisor?.full_name],
            ['nip', supervisor?.nip],
            ['position_title', supervisor?.position_title],
        ].forEach(([field, value]) => {
            const input = leaveForm.querySelector(`[name="supervisor_${field}"]`);

            if (input) {
                input.value = value ?? '';
            }
        });
    };

    const syncSupervisorOptions = () => {
        if (!supervisorSelect) {
            return;
        }

        const selectedEmployeeId = employeeSelect?.value;

        if (supervisorSelect.value === selectedEmployeeId) {
            clearSupervisorSelection();
        }

        updateSupervisorSearch();
        fillSupervisorDetails();
    };

    employeeSelect?.addEventListener('change', syncSupervisorOptions);
    supervisorSelect?.addEventListener('change', fillSupervisorDetails);
    syncSupervisorOptions();
    fillOfficial('official', authorizedOfficial, true);

    const applyOfficialDefaults = () => {
        if (plhToggle?.checked) {
            return;
        }

        const target = camatToggle?.checked ? walikotaOfficial : authorizedOfficial;

        if (!target) {
            return;
        }

        fillOfficial('official', target);
        officialHeading.textContent = camatToggle?.checked ? 'Pejabat berwenang (Wali Kota)' : 'Pejabat berwenang';
    };

    const supervisorInputs = [
        leaveForm.querySelector('[name="supervisor_employee_id"]'),
        leaveForm.querySelector('[name="supervisor_nip"]'),
        leaveForm.querySelector('[name="supervisor_position_title"]'),
        leaveForm.querySelector('[data-supervisor-search]'),
    ].filter(Boolean);

    const setSupervisorLocked = (locked) => {
        supervisorInputs.forEach((input) => {
            input.readOnly = locked;
            input.classList.toggle('cursor-not-allowed', locked);
            input.classList.toggle('bg-slate-50', locked);
            input.classList.toggle('text-slate-600', locked);
        });
    };

    const applySupervisorDefaults = () => {
        if (camatToggle?.checked) {
            supervisorHeading.textContent = 'Atasan langsung (Sekda)';
            fillField('supervisor_employee_id', '');
            fillField('supervisor_nip', sekdaOfficial?.nip ?? '');
            fillField('supervisor_position_title', sekdaOfficial?.position_title ?? '');
            const supervisorSearchInput = leaveForm.querySelector('[data-supervisor-search]');
            if (supervisorSearchInput) {
                supervisorSearchInput.value = sekdaOfficial?.full_name ?? '';
            }
            setSupervisorLocked(true);
            return;
        }

        supervisorHeading.textContent = 'Atasan langsung';
        setSupervisorLocked(false);
        clearSupervisorSelection();
    };

    camatToggle?.addEventListener('change', () => {
        applySupervisorDefaults();
        applyOfficialDefaults();
    });

    const setOfficialNameReadonly = (readonly) => {
        if (!plhSearch) {
            return;
        }

        plhSearch.readOnly = readonly;
        plhSearch.classList.toggle('cursor-not-allowed', readonly);
        plhSearch.classList.toggle('bg-slate-50', readonly);
        plhSearch.classList.toggle('text-slate-600', readonly);
    };

    plhToggle?.addEventListener('change', () => {
        if (plhToggle.checked) {
            plhSelect.value = '';
            plhSearch.value = '';
            setOfficialNameReadonly(false);
            fillField('official_nip', '');
            fillField('official_position_title', '');
            officialHeading.textContent = 'Pejabat berwenang (PLH)';
            return;
        }

        plhSelect.value = '';
        setOfficialNameReadonly(true);
        applyOfficialDefaults();
    });

    if (camatToggle?.checked) {
        applySupervisorDefaults();
        applyOfficialDefaults();
    }

    if (plhToggle?.checked) {
        setOfficialNameReadonly(false);

        if (plhSelect?.value) {
            const selectedPlhOption = Array.from(leaveForm.querySelectorAll('[data-plh-option]'))
                .find((option) => option.dataset.comboboxId === plhSelect.value);

            if (selectedPlhOption) {
                plhSearch.value = selectedPlhOption.dataset.fullName ?? '';
                fillField('official_nip', selectedPlhOption.dataset.nip ?? '');
                fillField('official_position_title', selectedPlhOption.dataset.positionTitle ?? '');
                officialHeading.textContent = 'Pejabat berwenang (PLH)';
            }
        }
    } else {
        setOfficialNameReadonly(true);
    }

    leaveForm.addEventListener('submit', (event) => {
        if (event.defaultPrevented) {
            return;
        }

        if (!startDate.value || !endDate.value || endDate.value < startDate.value) {
            event.preventDefault();
            dateRangePicker?.classList.add('date-range-picker-invalid');

            if (dateRangeSummary) {
                dateRangeSummary.textContent = 'Pilih setidaknya satu hari cuti terlebih dahulu.';
            }

            dateRangeGrid?.querySelector('[data-date-range-day]')?.focus();
            return;
        }

        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        isSubmitting = true;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
            submitButton.textContent = 'Membuat dokumen...';
        }
    });

    window.addEventListener('pageshow', () => {
        isSubmitting = false;
        renderDateRangePicker();

        if (submitButton) {
            submitButton.disabled = false;
            submitButton.removeAttribute('aria-busy');
            submitButton.textContent = submitLabel;
        }
    });
}

const leaveBalanceForm = document.querySelector('[data-leave-balance-form]');

if (leaveBalanceForm) {
    const balanceEmployeeCombobox = leaveBalanceForm.querySelector('[data-balance-employee-combobox]');
    const balanceEmployeeValue = leaveBalanceForm.querySelector('[data-balance-employee-value]');
    const balanceEmployeeSearch = leaveBalanceForm.querySelector('[data-balance-employee-search]');
    const balanceEmployeeSearchPanel = leaveBalanceForm.querySelector('[data-balance-employee-search-panel]');
    const balanceEmployeeSearchResult = leaveBalanceForm.querySelector('[data-balance-employee-search-result]');

    if (balanceEmployeeCombobox && balanceEmployeeValue && balanceEmployeeSearch && balanceEmployeeSearchPanel) {
        const balanceEmployeeOptions = Array.from(balanceEmployeeCombobox.querySelectorAll('[data-balance-employee-option]'));
        const normaliseBalanceEmployeeSearchValue = (value) => value
            .toLocaleLowerCase('id-ID')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9*]+/g, ' ')
            .trim();
        const escapeBalanceEmployeeRegularExpression = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const balanceEmployeeSearchMatcher = (query) => {
            const expression = normaliseBalanceEmployeeSearchValue(query)
                .split('*')
                .flatMap((part) => part.split(' '))
                .filter(Boolean)
                .map(escapeBalanceEmployeeRegularExpression)
                .join('.*');

            return expression ? new RegExp(expression, 'i') : null;
        };
        let activeBalanceEmployeeOptionIndex = -1;
        const visibleBalanceEmployeeOptions = () => balanceEmployeeOptions.filter((option) => !option.hidden);
        const clearActiveBalanceEmployeeOption = () => {
            activeBalanceEmployeeOptionIndex = -1;
            balanceEmployeeOptions.forEach((option) => {
                option.classList.remove('bg-sky-50', 'text-sky-950');
            });
            balanceEmployeeSearch.removeAttribute('aria-activedescendant');
        };
        const updateBalanceEmployeeSearch = () => {
            const matcher = balanceEmployeeSearchMatcher(balanceEmployeeSearch.value);
            let resultCount = 0;

            balanceEmployeeOptions.forEach((option) => {
                const matches = !matcher || matcher.test(normaliseBalanceEmployeeSearchValue(option.dataset.balanceEmployeeLabel));
                const listItem = option.closest('li');
                const index = option.querySelector('[data-balance-employee-option-index]');

                option.hidden = !matches;
                listItem.hidden = !matches;

                if (matches) {
                    resultCount += 1;
                    index.textContent = `${resultCount}.`;
                }
            });

            const hasQuery = balanceEmployeeSearch.value.trim() !== '';
            clearActiveBalanceEmployeeOption();

            if (!balanceEmployeeSearchResult) {
                return;
            }

            if (!hasQuery) {
                balanceEmployeeSearchResult.textContent = `${balanceEmployeeOptions.length} pegawai tersedia.`;
                return;
            }

            balanceEmployeeSearchResult.textContent = resultCount === 0
                ? 'Tidak ada pegawai yang cocok.'
                : `${resultCount} pegawai ditemukan.`;
        };
        const openBalanceEmployeeSearchPanel = () => {
            balanceEmployeeSearchPanel.hidden = false;
            balanceEmployeeSearch.setAttribute('aria-expanded', 'true');
        };
        const closeBalanceEmployeeSearchPanel = () => {
            balanceEmployeeSearchPanel.hidden = true;
            balanceEmployeeSearch.setAttribute('aria-expanded', 'false');
            clearActiveBalanceEmployeeOption();
        };
        const chooseBalanceEmployee = (option) => {
            balanceEmployeeValue.value = option.dataset.balanceEmployeeId;
            balanceEmployeeSearch.value = option.dataset.balanceEmployeeLabel;
            balanceEmployeeSearch.setCustomValidity('');
            balanceEmployeeOptions.forEach((item) => {
                item.setAttribute('aria-selected', String(item === option));
            });
            updateBalanceEmployeeSearch();
            closeBalanceEmployeeSearchPanel();
        };
        const activateBalanceEmployeeOption = (optionIndex) => {
            const visibleOptions = visibleBalanceEmployeeOptions();

            if (visibleOptions.length === 0) {
                return;
            }

            activeBalanceEmployeeOptionIndex = (optionIndex + visibleOptions.length) % visibleOptions.length;
            const activeOption = visibleOptions[activeBalanceEmployeeOptionIndex];

            balanceEmployeeOptions.forEach((option) => {
                const isActive = option === activeOption;

                option.classList.toggle('bg-sky-50', isActive);
                option.classList.toggle('text-sky-950', isActive);
            });
            balanceEmployeeSearch.setAttribute('aria-activedescendant', activeOption.id);
            activeOption.scrollIntoView({ block: 'nearest' });
        };

        balanceEmployeeSearch.addEventListener('focus', () => {
            updateBalanceEmployeeSearch();
            openBalanceEmployeeSearchPanel();

            if (balanceEmployeeValue.value) {
                balanceEmployeeSearch.select();
            }
        });
        balanceEmployeeSearch.addEventListener('input', () => {
            if (balanceEmployeeValue.value) {
                balanceEmployeeValue.value = '';
                balanceEmployeeOptions.forEach((option) => {
                    option.setAttribute('aria-selected', 'false');
                });
            }

            balanceEmployeeSearch.setCustomValidity('');
            updateBalanceEmployeeSearch();
            openBalanceEmployeeSearchPanel();
        });
        balanceEmployeeSearch.addEventListener('keydown', (event) => {
            const visibleOptions = visibleBalanceEmployeeOptions();

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                openBalanceEmployeeSearchPanel();
                activateBalanceEmployeeOption(activeBalanceEmployeeOptionIndex + 1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                openBalanceEmployeeSearchPanel();
                activateBalanceEmployeeOption(activeBalanceEmployeeOptionIndex - 1);
                return;
            }

            if (event.key === 'Enter' && (activeBalanceEmployeeOptionIndex >= 0 || visibleOptions.length === 1)) {
                event.preventDefault();
                chooseBalanceEmployee(visibleOptions[activeBalanceEmployeeOptionIndex >= 0 ? activeBalanceEmployeeOptionIndex : 0]);
                return;
            }

            if (event.key === 'Escape') {
                closeBalanceEmployeeSearchPanel();
            }

            if (event.key === 'Tab') {
                closeBalanceEmployeeSearchPanel();
            }
        });
        balanceEmployeeOptions.forEach((option) => {
            option.addEventListener('click', () => chooseBalanceEmployee(option));
        });
        document.addEventListener('click', (event) => {
            if (!balanceEmployeeCombobox.contains(event.target)) {
                closeBalanceEmployeeSearchPanel();
            }
        });
        leaveBalanceForm.addEventListener('submit', (event) => {
            if (balanceEmployeeValue.value) {
                return;
            }

            event.preventDefault();
            balanceEmployeeSearch.setCustomValidity('Pilih pegawai dari daftar.');
            balanceEmployeeSearch.reportValidity();
            openBalanceEmployeeSearchPanel();
        });
        updateBalanceEmployeeSearch();
    }
}

const wireFileName = (filePicker) => {
    const fileInput = filePicker.querySelector('[data-file-input]');
    const fileName = filePicker.querySelector('[data-file-name]');
    const initialFileName = fileName?.textContent?.trim() ?? '';

    const updateFileName = () => {
        const selectedFile = fileInput?.files?.[0];
        const label = selectedFile?.name ?? initialFileName;

        if (fileName) {
            fileName.textContent = label;
            fileName.title = label;
        }
    };

    fileInput?.addEventListener('change', updateFileName);

    return updateFileName;
};

const fileNameUpdates = Array.from(document.querySelectorAll('[data-file-picker]'))
    .map((filePicker) => wireFileName(filePicker));

window.addEventListener('pageshow', () => {
    fileNameUpdates.forEach((updateFileName) => updateFileName());
});

const employeeImportForm = document.querySelector('[data-employee-import-form]');

if (employeeImportForm) {
    const submitButton = employeeImportForm.querySelector('[data-submit-employee-import]');
    const submitLabel = submitButton?.querySelector('[data-submit-label]');
    const initialSubmitLabel = submitLabel?.textContent;
    let isSubmitting = false;

    employeeImportForm.addEventListener('submit', (event) => {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        isSubmitting = true;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
        }

        if (submitLabel) {
            submitLabel.textContent = 'Memeriksa file...';
        }
    });

    window.addEventListener('pageshow', () => {
        isSubmitting = false;

        if (submitButton) {
            submitButton.disabled = false;
            submitButton.removeAttribute('aria-busy');
        }

        if (submitLabel) {
            submitLabel.textContent = initialSubmitLabel;
        }
    });
}

const employeePerPageSelect = document.querySelector('[data-employee-per-page]');

if (employeePerPageSelect) {
    employeePerPageSelect.addEventListener('change', () => {
        employeePerPageSelect.form?.requestSubmit();
    });
}

const employeeActionMenus = Array.from(document.querySelectorAll('[data-employee-actions]'));

if (employeeActionMenus.length > 0) {
    let activeEmployeeActionMenu = null;

    const actionMenuToggle = (menu) => menu.querySelector('[data-employee-actions-toggle]');
    const actionMenuPanel = (menu) => menu.querySelector('[data-employee-actions-panel]');

    const closeEmployeeActionMenu = (menu, restoreFocus = false) => {
        const toggle = actionMenuToggle(menu);
        const panel = actionMenuPanel(menu);

        if (!toggle || !panel) {
            return;
        }

        panel.classList.remove('opacity-100', 'scale-100');
        panel.classList.add('pointer-events-none', 'opacity-0', 'scale-[0.96]');
        panel.setAttribute('aria-hidden', 'true');
        panel.setAttribute('inert', '');
        toggle.setAttribute('aria-expanded', 'false');

        if (activeEmployeeActionMenu === menu) {
            activeEmployeeActionMenu = null;
        }

        if (restoreFocus) {
            toggle.focus();
        }
    };

    const positionEmployeeActionMenu = (menu) => {
        const toggle = actionMenuToggle(menu);
        const panel = actionMenuPanel(menu);

        if (!toggle || !panel) {
            return;
        }

        const toggleBounds = toggle.getBoundingClientRect();
        const panelWidth = panel.offsetWidth;
        const panelHeight = panel.offsetHeight;
        const gap = 8;
        const viewportPadding = 12;
        const maxLeft = Math.max(viewportPadding, window.innerWidth - panelWidth - viewportPadding);
        const left = Math.min(Math.max(viewportPadding, toggleBounds.right - panelWidth), maxLeft);
        const topBelow = toggleBounds.bottom + gap;
        const topAbove = toggleBounds.top - panelHeight - gap;
        const top = topBelow + panelHeight <= window.innerHeight - viewportPadding || topAbove < viewportPadding
            ? topBelow
            : topAbove;

        panel.style.left = `${Math.round(left)}px`;
        panel.style.top = `${Math.round(top)}px`;
    };

    const openEmployeeActionMenu = (menu) => {
        const toggle = actionMenuToggle(menu);
        const panel = actionMenuPanel(menu);

        if (!toggle || !panel) {
            return;
        }

        if (activeEmployeeActionMenu && activeEmployeeActionMenu !== menu) {
            closeEmployeeActionMenu(activeEmployeeActionMenu);
        }

        panel.removeAttribute('inert');
        panel.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');
        positionEmployeeActionMenu(menu);
        panel.classList.remove('pointer-events-none', 'opacity-0', 'scale-[0.96]');
        panel.classList.add('opacity-100', 'scale-100');
        activeEmployeeActionMenu = menu;
    };

    employeeActionMenus.forEach((menu) => {
        const toggle = actionMenuToggle(menu);

        toggle?.addEventListener('click', (event) => {
            event.stopPropagation();

            if (activeEmployeeActionMenu === menu) {
                closeEmployeeActionMenu(menu);
                return;
            }

            openEmployeeActionMenu(menu);
        });

        menu.addEventListener('focusout', () => {
            window.setTimeout(() => {
                if (activeEmployeeActionMenu === menu && !menu.contains(document.activeElement)) {
                    closeEmployeeActionMenu(menu);
                }
            });
        });
    });

    document.addEventListener('click', (event) => {
        if (activeEmployeeActionMenu && !activeEmployeeActionMenu.contains(event.target)) {
            closeEmployeeActionMenu(activeEmployeeActionMenu);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeEmployeeActionMenu) {
            event.preventDefault();
            closeEmployeeActionMenu(activeEmployeeActionMenu, true);
        }
    });

    window.addEventListener('resize', () => {
        if (activeEmployeeActionMenu) {
            positionEmployeeActionMenu(activeEmployeeActionMenu);
        }
    });

    window.addEventListener('scroll', () => {
        if (activeEmployeeActionMenu) {
            closeEmployeeActionMenu(activeEmployeeActionMenu);
        }
    }, true);
}

document.querySelectorAll('label.form-label[for]').forEach((label) => {
    const field = document.getElementById(label.htmlFor);
    const isRequired = field?.required || field?.getAttribute?.('aria-required') === 'true';

    if (isRequired && !label.querySelector('.required-asterisk')) {
        const asterisk = document.createElement('span');

        asterisk.className = 'required-asterisk text-rose-500';
        asterisk.textContent = ' *';
        asterisk.setAttribute('aria-hidden', 'true');
        label.append(asterisk);
    }
});

const floatingFieldControlSelector = [
    'input.form-input:not([type="hidden"]):not([type="file"]):not([type="date"]):not([type="time"]):not([type="datetime-local"]):not([type="month"]):not([type="week"]):not([type="color"]):not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="search"]):not([type="number"]):not([readonly])',
    'textarea.form-textarea:not([readonly])',
].join(', ');

const isImportManagedField = (control) => Boolean(control.closest?.('[data-import-managed]'));

const floatingFieldSynchronizers = [];

document.querySelectorAll(floatingFieldControlSelector).forEach((control) => {
    if (isImportManagedField(control)) {
        return;
    }

    const fieldContainer = control.parentElement;

    if (!fieldContainer || !control.id) {
        return;
    }

    const label = Array.from(fieldContainer.children).find((child) =>
        child instanceof HTMLLabelElement && child.htmlFor === control.id,
    );

    if (!label) {
        return;
    }

    fieldContainer.classList.add('form-field-floating');

    if (control instanceof HTMLTextAreaElement) {
        fieldContainer.classList.add('is-textarea');
    }

    if (!control.getAttribute('placeholder')) {
        control.setAttribute('placeholder', ' ');
    }

    const syncFloatingField = () => {
        fieldContainer.classList.toggle('is-filled', control.value.trim().length > 0);
    };

    control.addEventListener('input', syncFloatingField);
    control.addEventListener('change', syncFloatingField);
    floatingFieldSynchronizers.push(syncFloatingField);
    syncFloatingField();

    if (control.maxLength < 0 || fieldContainer.querySelector('[data-form-field-counter]')) {
        return;
    }

    const counter = document.createElement('span');
    const updateCounter = () => {
        counter.textContent = `${control.value.length}/${control.maxLength}`;
    };

    fieldContainer.classList.add('has-counter');
    counter.className = 'form-field-counter';
    counter.dataset.formFieldCounter = '';
    counter.setAttribute('aria-hidden', 'true');
    control.addEventListener('input', updateCounter);
    control.addEventListener('change', updateCounter);
    updateCounter();
    fieldContainer.append(counter);
});

window.addEventListener('pageshow', () => {
    floatingFieldSynchronizers.forEach((syncFloatingField) => syncFloatingField());
});

document.querySelectorAll('[data-unit-admin-form]').forEach((form) => {
    const sourceControls = Array.from(form.querySelectorAll('input[name="department_source"]'));
    const sourceOptions = Array.from(form.querySelectorAll('[data-unit-source-option]'));
    const existingSection = form.querySelector('[data-existing-unit-section]');
    const newSection = form.querySelector('[data-new-unit-section]');
    const existingControl = form.querySelector('[data-existing-unit-control]');
    const newControls = Array.from(form.querySelectorAll('[data-new-unit-control]'));
    const departmentTypeControl = form.querySelector('[data-department-type-control]');
    const departmentCodeControl = form.querySelector('[data-department-code-control]');

    const departmentCodePrefix = () => departmentTypeControl?.value === 'kelurahan'
        ? 'KEL'
        : departmentTypeControl?.value === 'kecamatan'
            ? 'KEC'
            : '';

    const syncDepartmentCode = () => {
        if (!departmentTypeControl || !departmentCodeControl || departmentCodeControl.disabled) {
            return;
        }

        const prefix = departmentCodePrefix();

        if (!prefix) {
            return;
        }

        const rawValue = departmentCodeControl.value.trim();

        if (rawValue === '') {
            departmentCodeControl.value = '';
            return;
        }

        const suffix = rawValue
            .toUpperCase()
            .replace(/^(?:KEC|KEL)(?:[-_\s]*)/, '')
            .replace(/[^A-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

        departmentCodeControl.value = suffix ? `${prefix}-${suffix}` : `${prefix}-`;
    };

    const syncDepartmentSource = () => {
        const useExistingDepartment = sourceControls.find((control) => control.checked)?.value === 'existing';

        existingSection.hidden = !useExistingDepartment;
        newSection.hidden = useExistingDepartment;
        existingControl.disabled = !useExistingDepartment;
        existingControl.required = useExistingDepartment;

        newControls.forEach((control) => {
            control.disabled = useExistingDepartment;
            control.required = !useExistingDepartment && (
                ['department_type', 'department_name'].includes(control.id)
                || (control.id === 'department_parent_id' && departmentTypeControl?.value === 'kelurahan')
            );
        });
        sourceOptions.forEach((option) => {
            option.classList.toggle('is-selected', option.querySelector('input')?.checked ?? false);
        });
    };

    sourceControls.forEach((control) => control.addEventListener('change', syncDepartmentSource));
    departmentTypeControl?.addEventListener('change', () => {
        syncDepartmentCode();
        syncDepartmentSource();
    });
    departmentCodeControl?.addEventListener('input', syncDepartmentCode);
    syncDepartmentSource();
    syncDepartmentCode();
});

const accountTabs = document.querySelector('[data-account-tabs]');

if (accountTabs) {
    const accountTabButtons = Array.from(accountTabs.querySelectorAll('[data-account-tab]'));
    const accountTabPanels = Array.from(document.querySelectorAll('[data-account-tab-panel]'));

    const activateAccountTab = (tabId, shouldFocus = false) => {
        const activeButton = accountTabButtons.find((button) => button.dataset.accountTab === tabId);

        if (!activeButton) {
            return;
        }

        accountTabButtons.forEach((button) => {
            const isActive = button === activeButton;

            button.classList.toggle('account-tab-active', isActive);
            button.setAttribute('aria-selected', String(isActive));
            button.setAttribute('tabindex', isActive ? '0' : '-1');
        });

        accountTabPanels.forEach((panel) => {
            panel.hidden = panel.dataset.accountTabPanel !== tabId;
        });

        if (shouldFocus) {
            activeButton.focus();
        }
    };

    accountTabButtons.forEach((button) => {
        button.addEventListener('click', () => activateAccountTab(button.dataset.accountTab));
    });

    accountTabs.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
            return;
        }

        const currentIndex = accountTabButtons.indexOf(document.activeElement);

        if (currentIndex === -1) {
            return;
        }

        let nextIndex = currentIndex;

        if (event.key === 'ArrowLeft') {
            nextIndex = currentIndex === 0 ? accountTabButtons.length - 1 : currentIndex - 1;
        }

        if (event.key === 'ArrowRight') {
            nextIndex = currentIndex === accountTabButtons.length - 1 ? 0 : currentIndex + 1;
        }

        if (event.key === 'Home') {
            nextIndex = 0;
        }

        if (event.key === 'End') {
            nextIndex = accountTabButtons.length - 1;
        }

        event.preventDefault();
        activateAccountTab(accountTabButtons[nextIndex].dataset.accountTab, true);
    });

    activateAccountTab(accountTabs.dataset.accountTabDefault ?? accountTabButtons[0]?.dataset.accountTab);
}

const departmentEditorOverlay = document.querySelector('[data-department-editor-overlay]');

if (departmentEditorOverlay) {
    const departmentEditorDialog = departmentEditorOverlay.querySelector('[data-department-editor-dialog]');
    const departmentEditorForm = departmentEditorOverlay.querySelector('[data-department-editor-form]');
    const departmentEditorType = departmentEditorForm.querySelector('[data-department-editor-type]');
    const departmentEditorParent = departmentEditorForm.querySelector('[data-department-editor-parent]');
    const departmentEditorCode = departmentEditorForm.querySelector('[data-department-editor-code]');
    const departmentEditorName = departmentEditorForm.querySelector('#department_editor_name');
    const departmentEditorSimpegCode = departmentEditorForm.querySelector('#department_editor_simpeg_code');
    const departmentEditorPhone = departmentEditorForm.querySelector('#department_editor_phone');
    const departmentEditorAddress = departmentEditorForm.querySelector('#department_editor_address');
    const departmentEditorContext = departmentEditorForm.querySelector('[data-department-editor-context-input]');
    const departmentEditorId = departmentEditorForm.querySelector('[data-department-editor-id-input]');
    const departmentEditorMethod = departmentEditorForm.querySelector('[data-department-editor-method]');
    const departmentEditorTitle = departmentEditorOverlay.querySelector('[data-department-editor-title]');
    const departmentEditorDescription = departmentEditorOverlay.querySelector('[data-department-editor-description]');
    const departmentEditorSubmit = departmentEditorOverlay.querySelector('[data-department-editor-submit]');
    const departmentEditorCloseButton = departmentEditorOverlay.querySelector('[data-department-editor-close]');
    const departmentEditorCancel = departmentEditorOverlay.querySelector('[data-department-editor-cancel]');
    const parentOptions = Array.from(departmentEditorParent.options);
    let departmentEditorPreviouslyFocused = null;
    let closeDepartmentEditorTimeout = null;

    const normalizeEditorCode = () => {
        const prefix = departmentEditorType.value === 'kelurahan'
            ? 'KEL'
            : departmentEditorType.value === 'kecamatan'
                ? 'KEC'
                : '';

        if (!prefix) {
            return;
        }

        const suffix = departmentEditorCode.value
            .trim()
            .toUpperCase()
            .replace(/^(?:KEC|KEL)(?:[-_\s]*)/, '')
            .replace(/[^A-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

        departmentEditorCode.value = suffix ? `${prefix}-${suffix}` : '';
    };

    const syncEditorParent = () => {
        const isKelurahan = departmentEditorType.value === 'kelurahan';

        departmentEditorParent.disabled = !isKelurahan;
        departmentEditorParent.required = isKelurahan;

        if (!isKelurahan) {
            departmentEditorParent.value = '';
        }
    };

    const syncEditorFields = () => {
        normalizeEditorCode();
        syncEditorParent();
    };

    const hideCurrentParentOption = (departmentId) => {
        parentOptions.forEach((option) => {
            const isCurrentDepartment = departmentId !== '' && option.value === String(departmentId);

            option.hidden = isCurrentDepartment;
            option.disabled = isCurrentDepartment;
        });
    };

    const showDepartmentEditor = () => {
        departmentEditorOverlay.classList.remove('hidden', 'opacity-0');
        departmentEditorOverlay.classList.add('flex');
        departmentEditorOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        window.requestAnimationFrame(() => {
            departmentEditorOverlay.classList.add('opacity-100');
            departmentEditorDialog.classList.remove('translate-y-2', 'opacity-0');
            departmentEditorDialog.classList.add('translate-y-0', 'opacity-100');
        });
    };

    const closeDepartmentEditor = () => {
        departmentEditorOverlay.classList.remove('opacity-100');
        departmentEditorOverlay.classList.add('opacity-0');
        departmentEditorDialog.classList.remove('translate-y-0', 'opacity-100');
        departmentEditorDialog.classList.add('translate-y-2', 'opacity-0');
        window.clearTimeout(closeDepartmentEditorTimeout);
        closeDepartmentEditorTimeout = window.setTimeout(() => {
            departmentEditorOverlay.classList.add('hidden');
            departmentEditorOverlay.classList.remove('flex');
            departmentEditorOverlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            departmentEditorPreviouslyFocused?.focus();
            departmentEditorPreviouslyFocused = null;
        }, 160);
    };

    const setCreateMode = (preserveValues = false) => {
        departmentEditorForm.action = departmentEditorOverlay.dataset.departmentEditorStoreAction;
        departmentEditorMethod.removeAttribute('name');
        departmentEditorMethod.value = '';
        departmentEditorId.removeAttribute('name');
        departmentEditorId.value = '';
        departmentEditorContext.value = 'department';
        departmentEditorTitle.textContent = 'Tambah unit kerja';
        departmentEditorDescription.textContent = 'Unit dapat disimpan tanpa membuat akun Admin Unit.';
        departmentEditorSubmit.textContent = 'Simpan unit';
        hideCurrentParentOption('');

        if (!preserveValues) {
            departmentEditorForm.reset();
            departmentEditorType.value = '';
            departmentEditorCode.value = '';
            departmentEditorSimpegCode.value = '';
            departmentEditorParent.value = '';
            departmentEditorName.value = '';
            departmentEditorPhone.value = '';
            departmentEditorAddress.value = '';
        }

        syncEditorFields();
    };

    const setEditMode = (button = null, preserveValues = false) => {
        const departmentId = button?.dataset.departmentId ?? departmentEditorOverlay.dataset.departmentEditorOldId ?? '';
        const action = button?.dataset.departmentAction
            ?? departmentEditorOverlay.dataset.departmentEditorUpdateTemplate?.replace('__DEPARTMENT__', departmentId);

        departmentEditorForm.action = action;
        departmentEditorMethod.setAttribute('name', '_method');
        departmentEditorMethod.value = 'PUT';
        departmentEditorId.setAttribute('name', 'department_id');
        departmentEditorId.value = departmentId;
        departmentEditorContext.value = 'department-edit';
        departmentEditorTitle.textContent = 'Edit unit kerja';
        departmentEditorDescription.textContent = 'Perbarui metadata unit tanpa mengubah akun, pegawai, atau riwayat yang sudah tersimpan.';
        departmentEditorSubmit.textContent = 'Simpan perubahan';
        hideCurrentParentOption(departmentId);

        if (!preserveValues && button) {
            departmentEditorType.value = button.dataset.departmentType ?? '';
            departmentEditorCode.value = button.dataset.departmentCode ?? '';
            departmentEditorSimpegCode.value = button.dataset.departmentSimpegCode ?? '';
            departmentEditorParent.value = button.dataset.departmentParentId ?? '';
            departmentEditorName.value = button.dataset.departmentName ?? '';
            departmentEditorPhone.value = button.dataset.departmentPhone ?? '';
            departmentEditorAddress.value = button.dataset.departmentAddress ?? '';
        }

        syncEditorFields();
    };

    const openDepartmentEditor = (mode, button = null, preserveValues = false) => {
        window.clearTimeout(closeDepartmentEditorTimeout);
        departmentEditorPreviouslyFocused = document.activeElement;

        if (mode === 'edit') {
            setEditMode(button, preserveValues);
        } else {
            setCreateMode(preserveValues);
        }

        showDepartmentEditor();
        window.setTimeout(() => departmentEditorType.focus(), 0);
    };

    document.querySelectorAll('[data-department-create-open]').forEach((button) => {
        button.addEventListener('click', () => openDepartmentEditor('create', button));
    });

    document.querySelectorAll('[data-department-edit-open]').forEach((button) => {
        button.addEventListener('click', () => openDepartmentEditor('edit', button));
    });

    departmentEditorType.addEventListener('change', syncEditorFields);
    departmentEditorCode.addEventListener('input', normalizeEditorCode);
    departmentEditorCancel.addEventListener('click', closeDepartmentEditor);
    departmentEditorCloseButton.addEventListener('click', closeDepartmentEditor);
    departmentEditorOverlay.addEventListener('click', (event) => {
        if (event.target === departmentEditorOverlay) {
            closeDepartmentEditor();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !departmentEditorOverlay.classList.contains('hidden')) {
            event.preventDefault();
            closeDepartmentEditor();
        }
    });

    if (departmentEditorOverlay.dataset.departmentEditorOpen === 'true') {
        const mode = departmentEditorOverlay.dataset.departmentEditorContext === 'department-edit'
            ? 'edit'
            : 'create';

        openDepartmentEditor(mode, null, true);
    }
}

const departmentConfirmOverlay = document.querySelector('[data-department-confirm-overlay]');

if (departmentConfirmOverlay) {
    const departmentConfirmDialog = departmentConfirmOverlay.querySelector('[data-department-confirm-dialog]');
    const departmentConfirmTitle = departmentConfirmOverlay.querySelector('[data-department-confirm-title]');
    const departmentConfirmMessage = departmentConfirmOverlay.querySelector('[data-department-confirm-message]');
    const departmentConfirmIcon = departmentConfirmOverlay.querySelector('[data-department-confirm-icon]');
    const departmentConfirmNote = departmentConfirmOverlay.querySelector('[data-department-confirm-note]');
    const departmentConfirmSubmit = departmentConfirmOverlay.querySelector('[data-department-confirm-submit]');
    const departmentConfirmCancel = departmentConfirmOverlay.querySelector('[data-department-confirm-cancel]');
    let pendingDepartmentForm = null;
    let previouslyFocusedElement = null;
    let closeDepartmentConfirmTimeout = null;

    const setDepartmentConfirmState = (isDeactivation) => {
        departmentConfirmTitle.textContent = isDeactivation ? 'Nonaktifkan unit kerja?' : 'Aktifkan kembali unit kerja?';
        departmentConfirmSubmit.textContent = isDeactivation ? 'Nonaktifkan' : 'Aktifkan kembali';
        departmentConfirmSubmit.classList.toggle('btn-warning', isDeactivation);
        departmentConfirmSubmit.classList.toggle('btn-success', !isDeactivation);
        departmentConfirmIcon.classList.toggle('bg-amber-100', isDeactivation);
        departmentConfirmIcon.classList.toggle('text-amber-700', isDeactivation);
        departmentConfirmIcon.classList.toggle('bg-emerald-100', !isDeactivation);
        departmentConfirmIcon.classList.toggle('text-emerald-700', !isDeactivation);
        departmentConfirmNote.classList.toggle('bg-amber-50', isDeactivation);
        departmentConfirmNote.classList.toggle('text-amber-900', isDeactivation);
        departmentConfirmNote.classList.toggle('bg-emerald-50', !isDeactivation);
        departmentConfirmNote.classList.toggle('text-emerald-900', !isDeactivation);
    };

    const closeDepartmentConfirm = () => {
        pendingDepartmentForm = null;
        departmentConfirmOverlay.classList.remove('opacity-100');
        departmentConfirmOverlay.classList.add('opacity-0');
        departmentConfirmDialog.classList.remove('translate-y-0', 'opacity-100');
        departmentConfirmDialog.classList.add('translate-y-2', 'opacity-0');
        window.clearTimeout(closeDepartmentConfirmTimeout);
        closeDepartmentConfirmTimeout = window.setTimeout(() => {
            departmentConfirmOverlay.classList.add('hidden');
            departmentConfirmOverlay.classList.remove('flex');
            departmentConfirmOverlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            previouslyFocusedElement?.focus();
            previouslyFocusedElement = null;
        }, 160);
    };

    const openDepartmentConfirm = (form) => {
        const isDeactivation = form.dataset.departmentStatus === 'deactivate';
        const departmentName = form.dataset.departmentName ?? 'unit kerja ini';

        window.clearTimeout(closeDepartmentConfirmTimeout);
        pendingDepartmentForm = form;
        previouslyFocusedElement = document.activeElement;
        setDepartmentConfirmState(isDeactivation);
        departmentConfirmMessage.textContent = `${isDeactivation ? 'Nonaktifkan' : 'Aktifkan kembali'} unit kerja ${departmentName}? Data dan riwayat tetap tersimpan.`;
        departmentConfirmOverlay.classList.remove('hidden', 'opacity-0');
        departmentConfirmOverlay.classList.add('flex');
        departmentConfirmOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        window.requestAnimationFrame(() => {
            departmentConfirmOverlay.classList.add('opacity-100');
            departmentConfirmDialog.classList.remove('translate-y-2', 'opacity-0');
            departmentConfirmDialog.classList.add('translate-y-0', 'opacity-100');
        });

        departmentConfirmCancel.focus();
    };

    document.querySelectorAll('[data-department-confirm-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            openDepartmentConfirm(form);
        });
    });

    departmentConfirmCancel.addEventListener('click', closeDepartmentConfirm);
    departmentConfirmOverlay.addEventListener('click', (event) => {
        if (event.target === departmentConfirmOverlay) {
            closeDepartmentConfirm();
        }
    });
    departmentConfirmSubmit.addEventListener('click', () => {
        if (!pendingDepartmentForm) {
            return;
        }

        const form = pendingDepartmentForm;

        closeDepartmentConfirm();
        HTMLFormElement.prototype.submit.call(form);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !departmentConfirmOverlay.classList.contains('hidden')) {
            event.preventDefault();
            closeDepartmentConfirm();
        }
    });
}

document.querySelectorAll('[data-import-managed]').forEach((section) => {
    const note = document.createElement('p');
    note.className = 'mt-2 text-xs text-slate-400';
    note.textContent = 'Dikelola dari impor Gaji PNS. Tidak dapat diisi atau diubah di sini.';
    section.querySelector('summary')?.after(note);

    const lockField = (control) => {
        const isSelect = control.tagName === 'SELECT';

        if (isSelect) {
            control.disabled = true;
            Array.from(control.querySelectorAll('option[value=""]')).forEach((opt) => opt.remove());
        } else {
            control.readOnly = true;
        }

        control.classList.add('cursor-not-allowed', 'bg-slate-50', 'text-slate-600');

        control.addEventListener('focus', () => {
            if (control.readOnly || control.disabled) {
                control.blur();
            }
        });
    };

    section.querySelectorAll('input, textarea, select').forEach((control) => {
        if (control.type === 'hidden' || control.type === 'submit') {
            return;
        }

        lockField(control);
    });
});
