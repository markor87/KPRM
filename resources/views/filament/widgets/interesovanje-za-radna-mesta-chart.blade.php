@php
    $data = $this->getData();
    $ukupanBrojPrijava = $data['ukupanBrojPrijava'];
    $brojValidnihPrijava = $data['brojValidnihPrijava'];
    $brojOdbarenihPrijava = $data['brojOdbarenihPrijava'];
    $godina = $data['godina'];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Интересовање за радна места ({{ $godina }})
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
                                name: 'Број пријава',
                                data: [
                                    {{ $ukupanBrojPrijava }},
                                    {{ $brojValidnihPrijava }},
                                    {{ $brojOdbarenihPrijava }}
                                ]
                            }],
                            chart: {
                                type: 'bar',
                                height: 300,
                                toolbar: {
                                    show: false
                                },
                                background: 'transparent'
                            },
                            plotOptions: {
                                bar: {
                                    horizontal: true,
                                    borderRadius: 4,
                                    distributed: true,
                                    dataLabels: {
                                        position: 'top'
                                    }
                                }
                            },
                            dataLabels: {
                                enabled: true,
                                offsetX: 5,
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
                                    'Број пристиглих пријава',
                                    'Број валидних пријава',
                                    'Број одбачених пријава'
                                ],
                                labels: {
                                    style: {
                                        colors: textColor,
                                        fontSize: '11px'
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    style: {
                                        fontSize: '12px',
                                        colors: textColor
                                    },
                                    maxWidth: 'none'
                                }
                            },
                            colors: isDark ? ['#6b7cbe', '#8b9cd4', '#abbcee'] : ['#3b5998', '#5a7cbe', '#8ba3d4'],
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
                                        return val + ' пријава';
                                    }
                                }
                            }
                        };
                        this.chart = new ApexCharts(this.$refs.chartEl, options);
                        this.chart.render();
                    }
                }"
                style="height: 300px;"
            >
                <div x-ref="chartEl" style="height: 100%;"></div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
