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
                                    colors: ['#333']
                                },
                                formatter: function(val) {
                                    return val;
                                }
                            },
                            xaxis: {
                                categories: [
                                    'Број пристиглих пријава',
                                    'Број валидних пријава',
                                    'Број одбačених пријава'
                                ],
                                labels: {
                                    style: {
                                        colors: '#666'
                                    }
                                }
                            },
                            yaxis: {
                                labels: {
                                    style: {
                                        fontSize: '12px',
                                        colors: ['#333']
                                    },
                                    maxWidth: 'none'
                                }
                            },
                            colors: ['#3b5998', '#5a7cbe', '#8ba3d4'],
                            grid: {
                                borderColor: '#f1f1f1'
                            },
                            legend: {
                                show: false
                            },
                            tooltip: {
                                y: {
                                    formatter: function(val) {
                                        return val;
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
