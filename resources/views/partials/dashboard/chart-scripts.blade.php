<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var growthLabels = @json($monthlyLeadGrowth['labels'] ?? []);
        var growthData = @json($monthlyLeadGrowth['data'] ?? []);
        var sourceLabels = @json($leadSourceDistribution['labels'] ?? []);
        var sourceData = @json($leadSourceDistribution['data'] ?? []);

        var styles = getComputedStyle(document.documentElement);
        var accent = (styles.getPropertyValue('--crm-chart-1') || '#2563eb').trim();
        var palette = [
            (styles.getPropertyValue('--crm-chart-1') || '#2563eb').trim(),
            (styles.getPropertyValue('--crm-chart-2') || '#059669').trim(),
            (styles.getPropertyValue('--crm-chart-3') || '#d97706').trim(),
            (styles.getPropertyValue('--crm-chart-4') || '#7c3aed').trim(),
            (styles.getPropertyValue('--crm-chart-5') || '#0891b2').trim(),
            (styles.getPropertyValue('--crm-chart-6') || '#ea580c').trim(),
            (styles.getPropertyValue('--crm-chart-7') || '#64748b').trim()
        ];

        function markReady(shellId) {
            var shell = document.getElementById(shellId);
            if (shell) {
                shell.classList.remove('is-loading');
                shell.classList.add('is-ready');
                shell.setAttribute('aria-busy', 'false');
            }
        }

        function showEmpty(shellId, title, description) {
            var shell = document.getElementById(shellId);
            if (!shell) {
                return;
            }
            shell.classList.remove('is-loading');
            shell.classList.add('is-ready', 'is-empty');
            shell.setAttribute('aria-busy', 'false');
            shell.innerHTML =
                '<div class="crm-empty crm-empty--compact">' +
                    '<div class="crm-empty__icon" aria-hidden="true"><i class="fas fa-chart-pie"></i></div>' +
                    '<p class="crm-empty__title">' + title + '</p>' +
                    '<p class="crm-empty__desc">' + description + '</p>' +
                '</div>';
        }

        function hasChartData(values) {
            if (!values || !values.length) {
                return false;
            }
            return values.some(function (value) {
                return Number(value) > 0;
            });
        }

        var growthCanvas = document.getElementById('monthlyLeadGrowthChart');
        if (growthCanvas) {
            if (!hasChartData(growthData)) {
                showEmpty('monthly-lead-growth-shell', 'No lead growth yet', 'New leads will appear here as your pipeline builds.');
            } else {
                new Chart(growthCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: growthLabels,
                        datasets: [{
                            label: 'New leads',
                            data: growthData,
                            borderColor: accent,
                            backgroundColor: 'rgba(37, 99, 235, 0.08)',
                            borderWidth: 2,
                            pointRadius: 2.5,
                            pointHoverRadius: 4,
                            pointBackgroundColor: accent,
                            lineTension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: { display: false },
                        tooltips: {
                            backgroundColor: '#0f172a',
                            titleFontFamily: 'Source Sans Pro',
                            bodyFontFamily: 'Source Sans Pro',
                            cornerRadius: 6,
                            xPadding: 10,
                            yPadding: 8
                        },
                        scales: {
                            xAxes: [{
                                gridLines: { display: false, drawBorder: false },
                                ticks: { fontColor: '#94a3b8', fontSize: 11 }
                            }],
                            yAxes: [{
                                gridLines: { color: '#e2e8f0', zeroLineColor: '#e2e8f0', drawBorder: false },
                                ticks: {
                                    beginAtZero: true,
                                    precision: 0,
                                    fontColor: '#94a3b8',
                                    fontSize: 11,
                                    padding: 8
                                }
                            }]
                        }
                    }
                });
                markReady('monthly-lead-growth-shell');
            }
        }

        var sourceCanvas = document.getElementById('leadSourceChart');
        if (sourceCanvas) {
            if (!hasChartData(sourceData)) {
                showEmpty('lead-source-shell', 'No source data yet', 'Lead sources will chart here once leads are added.');
            } else {
                new Chart(sourceCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: sourceLabels,
                        datasets: [{
                            data: sourceData,
                            backgroundColor: palette,
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutoutPercentage: 68,
                        legend: {
                            position: window.innerWidth < 992 ? 'bottom' : 'right',
                            labels: {
                                boxWidth: 10,
                                fontColor: '#64748b',
                                fontSize: 11,
                                padding: 12
                            }
                        },
                        tooltips: {
                            backgroundColor: '#0f172a',
                            cornerRadius: 6,
                            xPadding: 10,
                            yPadding: 8
                        }
                    }
                });
                markReady('lead-source-shell');
            }
        }
    });
</script>
