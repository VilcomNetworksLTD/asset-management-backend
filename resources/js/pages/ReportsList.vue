<template>
  <div class="max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <h1 class="text-4xl font-black text-slate-800 tracking-tight">Intelligence <span class="text-vilcom-blue">Reports</span></h1>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2 flex items-center gap-2">
          <span class="size-1.5 bg-vilcom-orange rounded-full"></span>
          Data Aggregation & Analytical Exports
        </p>
      </div>
    </div>

    <!-- Report Types Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="report in reportTypes" :key="report.id" class="group bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-300 flex flex-col justify-between">
        <div class="space-y-4">
          <div class="size-12 bg-gray-50 rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-vilcom-blue group-hover:text-white transition-all">
             <component :is="getIcon(report.category)" class="size-6" />
          </div>
          <div>
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight">{{ report.name }}</h3>
            <p class="text-[10px] font-bold text-gray-400 leading-relaxed mt-2">{{ report.description }}</p>
          </div>
        </div>
        
        <button 
          :disabled="generatingId === report.id" 
          @click="generateReport(report)" 
          class="mt-8 flex items-center gap-2 text-[10px] font-black text-vilcom-blue uppercase tracking-widest group-hover:gap-3 transition-all disabled:opacity-30"
        >
          <FileSpreadsheet class="size-4" />
          {{ generatingId === report.id ? 'Aggregating...' : 'Compile Export' }}
        </button>
      </div>
    </div>

    <!-- History -->
    <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-8 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
        <h2 class="text-sm font-black text-slate-800 uppercase tracking-[0.2em] flex items-center gap-3">
          <History class="size-4 text-vilcom-blue" />
          Generated Data Sequences
        </h2>
        <button @click="loadHistory" class="p-3 bg-white border border-gray-100 rounded-xl text-slate-400 hover:text-vilcom-blue transition-all">
          <RefreshCcw class="size-4" />
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-50">
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Sequence Title</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Taxonomy</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Protocol</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Initiator</th>
              <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Generated At</th>
              <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] text-right">Access</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="item in history" :key="item.id" class="group hover:bg-slate-50 transition-colors">
              <td class="px-8 py-5">
                <div class="font-black text-slate-800 text-sm group-hover:text-vilcom-blue transition-colors">{{ item.title }}</div>
              </td>
              <td class="px-6 py-5">
                <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-black uppercase tracking-widest">{{ item.category }}</span>
              </td>
              <td class="px-6 py-5">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                  <FileText class="size-3 text-vilcom-orange" />
                  {{ item.type }}
                </div>
              </td>
              <td class="px-6 py-5 text-xs font-bold text-slate-600">{{ item.generated_by }}</td>
              <td class="px-6 py-5 text-[10px] font-bold text-gray-400 font-mono tracking-tighter">{{ item.created_at }}</td>
              <td class="px-8 py-5 text-right">
                <button @click="downloadReport(item.id)" class="p-2.5 bg-white border border-gray-100 text-vilcom-blue rounded-xl hover:bg-vilcom-blue hover:text-white hover:border-vilcom-blue transition-all shadow-sm">
                  <Download class="size-4" />
                </button>
              </td>
            </tr>
            <tr v-if="!history.length">
              <td colspan="6" class="p-20 text-center text-gray-400 font-bold uppercase text-[10px] tracking-widest">
                No active export history detected.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>


<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'
import { FileText, Download, RefreshCcw, PieChart, FileSpreadsheet, ClipboardList, Shield, HardDrive, Package, Users, History, Activity, ChevronLeft, ChevronRight, Search } from 'lucide-vue-next';


const history = ref([])
const generatingId = ref(null)

const getIcon = (category) => {
  const map = {
    'inventory': HardDrive,
    'maintenance': Activity,
    'assignments': Users,
    'ssl': Shield,
    'accessories': Package,
    'licenses': ClipboardList,
    'people': Users,
    'activity logs': History
  };
  return map[category.toLowerCase()] || FileText;
};

const reportTypes = [

  { id: 1, name: 'Asset Inventory', category: 'inventory', description: 'Full list of all current assets and their statuses.' },
  { id: 2, name: 'Maintenance Summary', category: 'maintenance', description: 'Overview of repair costs and completion dates.' },
  { id: 3, name: 'User Assignments', category: 'assignments', description: 'List of assets currently assigned to people.' },
  { id: 4, name: 'SSL Certificates', category: 'ssl', description: 'SSL certificate expiry dates, vendors, and statuses.' },
  { id: 5, name: 'Accessories', category: 'accessories', description: 'Accessory quantities, categories, and values.' },
  { id: 7, name: 'Licenses', category: 'licenses', description: 'License seats, manufacturers, and pricing details.' },
  { id: 8, name: 'People', category: 'people', description: 'Users and roles for audit and access reporting.' },
  { id: 9, name: 'Activity Logs', category: 'activity logs', description: 'Audit trail of actions performed in the system.' },
]

const loadHistory = async () => {
  const { data } = await axios.get('/api/reports-history')
  history.value = data || []
}

const generateReport = async (report) => {
  try {
    generatingId.value = report.id
    await axios.post('/api/reports/generate', { category: report.category })
    await loadHistory()
    alert(`${report.name} generated successfully.`)
  } catch (e) {
    alert('Failed to generate report.')
  } finally {
    generatingId.value = null
  }
}

const downloadReport = async (id) => {
  try {
    const response = await axios.get(`/api/reports/${id}/download`, {
      responseType: 'blob',
      validateStatus: () => true,
    })

    if (response.status >= 400) {
      const text = await response.data.text()
      try {
        const parsed = JSON.parse(text)
        throw new Error(parsed.message || `Download failed (HTTP ${response.status})`)
      } catch {
        throw new Error(text || `Download failed (HTTP ${response.status})`)
      }
    }

    const disposition = response.headers['content-disposition'] || ''
    const match = disposition.match(/filename="?([^\"]+)"?/)
    const filename = match?.[1] || `report-${id}.csv`

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    alert(e?.message || 'Failed to download report.')
  }
}

onMounted(loadHistory)
</script>