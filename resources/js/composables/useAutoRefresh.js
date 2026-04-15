import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useWindowFocus } from '@vueuse/core'

export function useAutoRefresh(callback, options = {}) {
  const {
    interval = 30000,
    enabled = true,
    immediate = true,
    refetchOnFocus = true,
    refetchOnVisibility = true
  } = options

  const isLoading = ref(false)
  const lastUpdated = ref(null)
  const error = ref(null)
  const isFocused = useWindowFocus()
  
  let intervalId = null
  let isActive = ref(true)

  const fetchData = async () => {
    if (!enabled || !isActive.value) return
    
    isLoading.value = true
    error.value = null
    
    try {
      await callback()
      lastUpdated.value = new Date()
    } catch (e) {
      error.value = e
      console.error('Auto-refresh error:', e)
    } finally {
      isLoading.value = false
    }
  }

  const startInterval = () => {
    if (intervalId) clearInterval(intervalId)
    
    if (enabled && interval > 0) {
      intervalId = setInterval(() => {
        if (isActive.value) {
          fetchData()
        }
      }, interval)
    }
  }

  const stopInterval = () => {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
  }

  const refresh = () => {
    return fetchData()
  }

  const setActive = (active) => {
    isActive.value = active
    if (active && enabled) {
      fetchData()
      startInterval()
    }
  }

  watch(isFocused, (focused) => {
    if (refetchOnFocus && focused) {
      fetchData()
    }
  })

  onMounted(() => {
    if (immediate) {
      fetchData()
    }
    startInterval()
  })

  onUnmounted(() => {
    stopInterval()
  })

  return {
    isLoading,
    lastUpdated,
    error,
    refresh,
    setActive,
    fetchData
  }
}

export function useListRefresh(fetchCallback, options = {}) {
  const {
    interval = 30000,
    refetchOnFocus = true,
    staleTime = 5000
  } = options

  const items = ref([])
  const loading = ref(true)
  const isFocused = useWindowFocus()
  const lastFetch = ref(0)

  const fetchItems = async () => {
    const now = Date.now()
    if (now - lastFetch.value < staleTime && items.value.length > 0) {
      return
    }

    loading.value = true
    try {
      const result = await fetchCallback()
      items.value = result
      lastFetch.value = now
    } catch (e) {
      console.error('List refresh error:', e)
    } finally {
      loading.value = false
    }
  }

  watch(isFocused, (focused) => {
    if (refetchOnFocus && focused) {
      fetchItems()
    }
  })

  onMounted(() => {
    fetchItems()
    
    if (interval > 0) {
      setInterval(fetchItems, interval)
    }
  })

  return {
    items,
    loading,
    refresh: fetchItems
  }
}

export default useAutoRefresh
