@php
    $data = $this->getData();
    $labels = $data['labels'];
    $averages = $data['averages'];
    $godina = $data['godina'];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Просечно време трајања фаза јавних конкурсних поступака изражено у данима ({{ $godina }})
        </x-slot>

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
                            name: 'Просечан број дана',
                            data: @js($averages)
                        }],
                        chart: {
                            type: 'bar',
                            height: 600,
                            toolbar: {
                                show: false
                            },
                            background: 'transparent'
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                borderRadius: 4,
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
                            categories: @js($labels),
                            title: {
                                text: 'Број дана',
                                style: {
                                    color: textColor,
                                    fontSize: '12px'
                                }
                            },
                            labels: {
                                style: {
                                    colors: textColor,
                                    fontSize: '11px'
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                maxWidth: 450,
                                style: {
                                    fontSize: '10px',
                                    colors: textColor
                                }
                            }
                        },
                        colors: [isDark ? '#f87171' : '#dc2626'],
                        grid: {
                            borderColor: gridColor,
                            strokeDashArray: 3,
                            padding: {
                                left: 20,
                                right: 40
                            }
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light',
                            y: {
                                formatter: function(val) {
                                    return val + ' дана';
                                }
                            }
                        }
                    };
                    this.chart = new ApexCharts(this.$refs.chartEl, options);
                    this.chart.render();
                }
            }"
            style="height: 650px;"
        >
            <div x-ref="chartEl" style="height: 100%;"></div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
