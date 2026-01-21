@php
    $data = $this->getData();
    $javni = $data['javni'];
    $izborna = $data['izborna'];
    $godina = $data['godina'];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Трајање јавних конкурсних и изборних поступака ({{ $godina }})
        </x-slot>

        <div class="space-y-8">
            {{-- JAVNI KONKURSI - Konkursni postupak --}}
            <div>
                <h3 class="text-base font-semibold mb-4 text-gray-700 dark:text-gray-300">
                    Трајање јавних конкурсних поступака:
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Najkraci --}}
                    <div style="background: #dc2626; border-radius: 8px; padding: 24px; box-shadow: 0 10px 15px rgba(0,0,0,0.1); min-height: 150px;">
                        <div style="text-align: center;">
                            <div style="font-size: 3rem; font-weight: bold; color: white; margin-bottom: 8px;">
                                {{ $javni['min'] }}
                            </div>
                            <div style="font-size: 0.875rem; font-weight: 500; color: white; opacity: 0.9; margin-bottom: 12px;">
                                дана
                            </div>
                            <div style="display: inline-block; background: #991b1b; padding: 6px 16px; border-radius: 6px;">
                                <span style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: white;">
                                    НАЈКРАЋИ
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Prosek --}}
                    <div style="background: #dc2626; border-radius: 8px; padding: 24px; box-shadow: 0 10px 15px rgba(0,0,0,0.1); min-height: 150px;">
                        <div style="text-align: center;">
                            <div style="font-size: 3rem; font-weight: bold; color: white; margin-bottom: 8px;">
                                {{ $javni['avg'] }}
                            </div>
                            <div style="font-size: 0.875rem; font-weight: 500; color: white; opacity: 0.9; margin-bottom: 12px;">
                                дана
                            </div>
                            <div style="display: inline-block; background: #991b1b; padding: 6px 16px; border-radius: 6px;">
                                <span style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: white;">
                                    ПРОСЕК
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Najduzi --}}
                    <div style="background: #dc2626; border-radius: 8px; padding: 24px; box-shadow: 0 10px 15px rgba(0,0,0,0.1); min-height: 150px;">
                        <div style="text-align: center;">
                            <div style="font-size: 3rem; font-weight: bold; color: white; margin-bottom: 8px;">
                                {{ $javni['max'] }}
                            </div>
                            <div style="font-size: 0.875rem; font-weight: 500; color: white; opacity: 0.9; margin-bottom: 12px;">
                                дана
                            </div>
                            <div style="display: inline-block; background: #991b1b; padding: 6px 16px; border-radius: 6px;">
                                <span style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: white;">
                                    НАЈДУЖИ
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- JAVNI KONKURSI - Izborni postupak --}}
            <div>
                <h3 class="text-base font-semibold mb-4 text-gray-700 dark:text-gray-300">
                    Трајање јавних изборних поступака:
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Najkraci --}}
                    <div style="background: #1e40af; border-radius: 8px; padding: 24px; box-shadow: 0 10px 15px rgba(0,0,0,0.1); min-height: 150px;">
                        <div style="text-align: center;">
                            <div style="font-size: 3rem; font-weight: bold; color: white; margin-bottom: 8px;">
                                {{ $izborna['min'] }}
                            </div>
                            <div style="font-size: 0.875rem; font-weight: 500; color: white; opacity: 0.9; margin-bottom: 12px;">
                                дана
                            </div>
                            <div style="display: inline-block; background: #172554; padding: 6px 16px; border-radius: 6px;">
                                <span style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: white;">
                                    НАЈКРАЋИ
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Prosek --}}
                    <div style="background: #1e40af; border-radius: 8px; padding: 24px; box-shadow: 0 10px 15px rgba(0,0,0,0.1); min-height: 150px;">
                        <div style="text-align: center;">
                            <div style="font-size: 3rem; font-weight: bold; color: white; margin-bottom: 8px;">
                                {{ $izborna['avg'] }}
                            </div>
                            <div style="font-size: 0.875rem; font-weight: 500; color: white; opacity: 0.9; margin-bottom: 12px;">
                                дана
                            </div>
                            <div style="display: inline-block; background: #172554; padding: 6px 16px; border-radius: 6px;">
                                <span style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: white;">
                                    ПРОСЕК
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Najduzi --}}
                    <div style="background: #1e40af; border-radius: 8px; padding: 24px; box-shadow: 0 10px 15px rgba(0,0,0,0.1); min-height: 150px;">
                        <div style="text-align: center;">
                            <div style="font-size: 3rem; font-weight: bold; color: white; margin-bottom: 8px;">
                                {{ $izborna['max'] }}
                            </div>
                            <div style="font-size: 0.875rem; font-weight: 500; color: white; opacity: 0.9; margin-bottom: 12px;">
                                дана
                            </div>
                            <div style="display: inline-block; background: #172554; padding: 6px 16px; border-radius: 6px;">
                                <span style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: white;">
                                    НАЈДУЖИ
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
