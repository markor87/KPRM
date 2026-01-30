@php
    $data = $this->getData();
    $brojValidnihPrijava = $data['brojValidnihPrijava'];
    $brojKandidataOfk = $data['brojKandidataOfk'];
    $brojKandidataPfk = $data['brojKandidataPfk'];
    $brojKandidataPk = $data['brojKandidataPk'];
    $brojOdazvanihIntervju = $data['brojOdazvanihIntervju'];
    $brojKandidataNaListi = $data['brojKandidataNaListi'];
    $godina = $data['godina'];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Селекција кандидата ({{ $godina }})
        </x-slot>

        <div style="padding: 20px;">
            <div
                x-data="{
                    chart: null,
                    init() {
                        this.loadChart();
                    },
                    loadChart() {
                        if (typeof ApexCharts === 'undefined') {
                            const script = document.createElement('script');
                            script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                            script.onload = () => this.renderChart();
                            document.head.appendChild(script);
                        } else {
                            this.renderChart();
                        }
                    },
                    renderChart() {
                        const isDark = document.documentElement.classList.contains('dark');
                        const textColor = isDark ? '#d1d5db' : '#374151';
                        const gridColor = isDark ? '#4b5563' : '#e5e7eb';

                        const options = {
                            series: [{
                                name: 'Број кандидата',
                                data: [
                                    {{ $brojValidnihPrijava }},
                                    {{ $brojKandidataOfk }},
                                    {{ $brojKandidataPfk }},
                                    {{ $brojKandidataPk }},
                                    {{ $brojOdazvanihIntervju }},
                                    {{ $brojKandidataNaListi }}
                                ]
                            }],
                            chart: {
                                type: 'bar',
                                height: 350,
                                toolbar: {
                                    show: false
                                },
                                background: 'transparent'
                            },
                            plotOptions: {
                                bar: {
                                    horizontal: false,
                                    columnWidth: '60%',
                                    borderRadius: 4,
                                    dataLabels: {
                                        position: 'top'
                                    }
                                }
                            },
                            dataLabels: {
                                enabled: true,
                                offsetY: -20,
                                style: {
                                    fontSize: '13px',
                                    fontWeight: 'bold',
                                    colors: [textColor]
                                },
                                formatter: function(val) {
                                    return val;
                                }
                            },
                            xaxis: {
                                categories: [
                                    'Валидне пријаве',
                                    'ОФК',
                                    'ПФК',
                                    'ПК',
                                    'Интервју',
                                    'Листа'
                                ],
                                labels: {
                                    style: {
                                        colors: textColor,
                                        fontSize: '12px'
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    style: {
                                        fontSize: '12px',
                                        colors: textColor
                                    }
                                }
                            },
                            colors: [isDark ? '#60a5fa' : '#3b82f6'],
                            grid: {
                                borderColor: gridColor,
                                strokeDashArray: 3
                            },
                            legend: {
                                show: false
                            },
                            tooltip: {
                                theme: isDark ? 'dark' : 'light',
                                y: {
                                    formatter: function(val) {
                                        return val + ' кандидата';
                                    }
                                }
                            }
                        };
                        this.chart = new ApexCharts(this.$refs.chartEl, options);
                        this.chart.render();
                    }
                }"
                style="height: 350px;"
            >
                <div x-ref="chartEl" style="height: 100%;"></div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
