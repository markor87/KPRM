@php
    $data = $this->getData();
    $uspesno = $data['uspesno'];
    $neuspeo = $data['neuspeo'];
    $obustavljeno = $data['obustavljeno'];
    $uspesnoProcenat = $data['uspesnoProcenat'];
    $neuspeoProcenat = $data['neuspeoProcenat'];
    $obustavljenoProcenat = $data['obustavljenoProcenat'];
    $godina = $data['godina'];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Степен успешности {{ $godina }}
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
                    const textColor = isDark ? '#e5e7eb' : '#333';

                    const options = {
                        series: [{{ $uspesno }}, {{ $neuspeo }}, {{ $obustavljeno }}],
                        chart: {
                            type: 'donut',
                            height: 280
                        },
                        labels: ['Успешно завршен конкурс', 'Неуспео конкурс', 'Обустављен конкурс'],
                        colors: ['#3b82f6', '#f59e0b', '#dc2626'],
                        legend: {
                            show: true,
                            position: 'top',
                            labels: {
                                colors: textColor
                            }
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '65%',
                                    labels: {
                                        show: false
                                    }
                                }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return Math.round(val) + '%';
                            },
                            style: {
                                fontSize: '14px',
                                fontWeight: 'bold',
                                colors: ['#fff']
                            },
                            dropShadow: {
                                enabled: false
                            }
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light'
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
    </x-filament::section>
</x-filament-widgets::widget>
