import { ref, onMounted } from 'vue';
import axios from 'axios';

// very lightweight composable that retrieves the system settings once and
// keeps them in a reactive reference.  callers can re-use the same instance
// by importing and invoking the function.

const settings = ref(null);
let initialized = false;

export function useSettings() {
    if (!initialized) {
        initialized = true;
        settings.value = {
            currency: 'KES',
            system_name: '',
            // other defaults may be added here
        };
        onMounted(async() => {
            try {
                const res = await axios.get('/api/settings');
                settings.value = {...settings.value, ...res.data };
            } catch (e) {
                console.warn('unable to fetch settings:', e);
            }
        });
    }

    return { settings };
}