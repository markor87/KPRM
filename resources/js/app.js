import './bootstrap';

// Import Livewire and Alpine
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Make Alpine available globally
window.Alpine = Alpine;

// Start Livewire
Livewire.start();
