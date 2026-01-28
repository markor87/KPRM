@php
    $data = $this->getData();
    $javni = $data['javni'];
    $interni = $data['interni'];
    $godina = $data['godina'];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Оглашени конкурси {{ $godina }}
        </x-slot>

        <div
            x-data="{
                chart: null,
                init() {
                    console.log('Alpine init');
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
                    console.log('Rendering chart');
                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#e5e7eb' : '#333';

                    const options = {
                        series: [{{ $javni }}, {{ $interni }}],
                        chart: {
                            type: 'donut',
                            height: 280
                        },
                        labels: ['Јавни: {{ $javni }}', 'Интерни: {{ $interni }}'],
                        colors: ['#3b82f6', '#10b981'],
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
