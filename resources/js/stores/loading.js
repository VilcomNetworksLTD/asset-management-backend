import { ref } from 'vue'

const isLoading = ref(false)
let requestCount = 0

export function useLoading() {

    const start = () => {
        requestCount++
        isLoading.value = true
    }

    const stop = () => {
        requestCount--
        if (requestCount <= 0) {
            requestCount = 0
            isLoading.value = false
        }
    }

    return {
        isLoading,
        start,
        stop
    }
}