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
                            }
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
                                colors: ['#333']
                            },
                            formatter: function(val) {
                                return val + ' дана';
                            }
                        },
                        xaxis: {
                            categories: @js($labels),
                            title: {
                                text: 'Број дана'
                            }
                        },
                        yaxis: {
                            labels: {
                                maxWidth: 450,
                                style: {
                                    fontSize: '10px'
                                }
                            }
                        },
                        colors: ['#dc2626'],
                        grid: {
                            borderColor: '#f1f1f1',
                            padding: {
                                left: 20,
                                right: 40
                            }
                        },
                        tooltip: {
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
