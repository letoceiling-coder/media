<template>
  <div class="space-y-6 bg-transparent">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button
          v-if="selectedFolder"
          @click="handleBack"
          class="h-10 w-10 flex items-center justify-center rounded-lg hover:bg-accent/10 hover:text-accent transition-colors"
        >
          ←
        </button>
        <div>
          <h1 class="text-3xl font-semibold text-foreground">
            {{ selectedFolder ? `Медиа менеджер - ${selectedFolder.name}` : 'Медиа менеджер - Список папок' }}
          </h1>
          <p class="text-muted-foreground mt-1">
            {{ selectedFolder ? 'Загрузка файлов' : 'Управление медиа файлами' }}
          </p>
        </div>
      </div>
      <div class="flex gap-2">
        <div v-if="!selectedFolder && !selectionMode">
          <button
            @click="handleToggleCreateFolder"
            :disabled="loading"
            class="h-11 px-6 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl shadow-lg shadow-accent/10 inline-flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span>+</span>
            <span>{{ loading ? 'Создание...' : 'Создать папку' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && folders.length === 0" class="flex items-center justify-center py-12">
      <p class="text-muted-foreground">Загрузка папок...</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
      <p class="text-destructive">{{ error }}</p>
    </div>

    <!-- Search -->
    <div v-if="!selectedFolder && !loading" class="relative">
      <span class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground">🔍</span>
      <input
        type="text"
        placeholder="Поиск папок..."
        v-model="searchQuery"
        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 pl-9 text-sm"
      />
    </div>

    <!-- Folders Grid -->
    <div v-if="!selectedFolder && !loading" class="grid gap-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
      <div
        v-for="folder in filteredFolders"
        :key="folder.id"
        class="group relative"
      >
        <div
          class="cursor-pointer"
          @click="handleFolderClick(folder)"
        >
          <div class="relative aspect-square mb-2 bg-transparent rounded-lg overflow-hidden flex items-center justify-center">
            <img 
              :src="getFolderIcon(folder)" 
              :alt="folder.name"
              class="w-full h-full object-contain max-w-[66.67%] max-h-[66.67%]"
              @error="handleFolderImageError"
            />
            <div class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded z-10">
              {{ folder.count || 0 }}
            </div>
          </div>
          <p class="text-sm font-medium text-center text-foreground truncate">{{ folder.name }}</p>
          <p class="text-xs text-muted-foreground text-center">{{ folder.count || 0 }} файлов</p>
        </div>
        <button
          v-if="!folder.protected && !selectionMode"
          @click.stop="handleDeleteFolder(folder)"
          class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity w-6 h-6 flex items-center justify-center bg-destructive text-white rounded text-xs hover:bg-destructive/90"
          title="Удалить папку"
        >
          ✕
        </button>
        <div
          v-if="folder.protected"
          class="absolute top-2 right-2 w-6 h-6 flex items-center justify-center bg-accent/20 text-accent rounded text-xs"
          title="Защищенная папка"
        >
          🔒
        </div>
      </div>
    </div>

    <!-- Upload Interface -->
    <div v-if="selectedFolder && !loading" class="space-y-4">
      <!-- Upload Area -->
      <div
        @drop.prevent="handleDrop"
        @dragover.prevent
        @dragenter.prevent
        class="border-2 border-dashed border-input rounded-lg p-8 text-center hover:border-accent/50 transition-colors"
      >
        <p class="text-muted-foreground mb-4">Перетащите файлы сюда или нажмите для выбора</p>
        <input
          type="file"
          ref="fileInput"
          @change="handleFileSelect"
          multiple
          class="hidden"
        />
        <button
          @click="$refs.fileInput.click()"
          :disabled="uploading"
          class="px-6 py-2 bg-accent/10 text-accent rounded-lg hover:bg-accent/20 disabled:opacity-50"
        >
          {{ uploading ? 'Загрузка...' : 'Выбрать файлы' }}
        </button>
      </div>

      <!-- Files Grid -->
      <div class="grid gap-4 md:grid-cols-4 lg:grid-cols-6">
        <div
          v-for="file in files"
          :key="file.id"
          class="group relative"
        >
          <div
            class="cursor-pointer"
            @click="handleFileClick(file)"
          >
            <div class="relative aspect-square mb-2 bg-transparent rounded-lg overflow-hidden flex items-center justify-center">
              <img
                v-if="file.type === 'photo'"
                :src="getFileUrl(file)"
                :alt="file.original_name"
                class="w-full h-full object-cover"
                @error="handleFileImageError"
              />
              <div
                v-else
                class="w-full h-full flex items-center justify-center bg-muted"
              >
                <img 
                  :src="getFileTypeIcon(file.type)" 
                  :alt="file.type"
                  class="w-16 h-16 object-contain"
                />
              </div>
              <div
                v-if="selectionMode"
                class="absolute top-2 left-2"
              >
                <input
                  type="checkbox"
                  :checked="isFileSelected(file)"
                  @click.stop="toggleFileSelection(file)"
                  class="w-5 h-5"
                />
              </div>
            </div>
            <p class="text-sm font-medium text-center text-foreground truncate">{{ file.original_name }}</p>
            <p class="text-xs text-muted-foreground text-center">{{ formatFileSize(file.size) }}</p>
          </div>
          <div
            v-if="!selectionMode"
            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1"
          >
            <!-- Редактировать (только для фото) - открывает файл для просмотра, если нет роута редактирования -->
            <button
              v-if="file.type === 'photo'"
              @click.stop="handleEditFile(file)"
              class="flex-1 h-9 flex items-center justify-center bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors"
              title="Редактировать/Просмотр"
            >
              <span class="text-sm">✏️</span>
            </button>
            <!-- Переместить -->
            <button
              @click.stop="handleMoveFile(file)"
              class="flex-1 h-9 flex items-center justify-center bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors"
              title="Переместить"
            >
              <span class="text-sm">📁</span>
            </button>
            <!-- Удалить -->
            <button
              @click.stop="handleDeleteFile(file)"
              class="flex-1 h-9 flex items-center justify-center bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors"
              title="Удалить"
            >
              <span class="text-sm">🗑️</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2">
        <button
          @click="loadPage(pagination.current_page - 1)"
          :disabled="!pagination.prev_page_url"
          class="px-4 py-2 bg-accent/10 text-accent rounded-lg hover:bg-accent/20 disabled:opacity-50"
        >
          Предыдущая
        </button>
        <span class="px-4 py-2 text-muted-foreground">
          Страница {{ pagination.current_page }} из {{ pagination.last_page }}
        </span>
        <button
          @click="loadPage(pagination.current_page + 1)"
          :disabled="!pagination.next_page_url"
          class="px-4 py-2 bg-accent/10 text-accent rounded-lg hover:bg-accent/20 disabled:opacity-50"
        >
          Следующая
        </button>
      </div>
    </div>

    <!-- Create Folder Modal -->
    <div
      v-if="showCreateFolderModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="showCreateFolderModal = false"
    >
      <div class="bg-background rounded-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-semibold mb-4">Создать папку</h2>
        <input
          v-model="newFolderName"
          @keyup.enter="handleCreateFolder"
          type="text"
          placeholder="Название папки"
          class="w-full px-4 py-2 border border-input rounded-lg mb-4"
        />
        <div class="flex gap-2 justify-end">
          <button
            @click="showCreateFolderModal = false"
            class="px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
          >
            Отмена
          </button>
          <button
            @click="handleCreateFolder"
            :disabled="!newFolderName.trim()"
            class="px-4 py-2 bg-accent/10 text-accent rounded-lg hover:bg-accent/20 disabled:opacity-50"
          >
            Создать
          </button>
        </div>
      </div>
    </div>

    <!-- Move File Modal -->
    <div
      v-if="showMoveModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="showMoveModal = false"
    >
      <div class="bg-background rounded-lg p-6 w-full max-w-md max-h-[80vh] overflow-y-auto">
        <h2 class="text-xl font-semibold mb-4">Переместить файл</h2>
        <div class="space-y-2 max-h-64 overflow-y-auto">
          <div
            v-for="folder in allFolders"
            :key="folder.id"
            @click="handleSelectMoveFolder(folder)"
            class="p-2 cursor-pointer hover:bg-accent/10 rounded-lg"
            :class="{ 'bg-accent/20': moveTargetFolder?.id === folder.id }"
          >
            {{ folder.name }}
          </div>
        </div>
        <div class="flex gap-2 justify-end mt-4">
          <button
            @click="showMoveModal = false"
            class="px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80"
          >
            Отмена
          </button>
          <button
            @click="handleConfirmMove"
            :disabled="!moveTargetFolder"
            class="px-4 py-2 bg-accent/10 text-accent rounded-lg hover:bg-accent/20 disabled:opacity-50"
          >
            Переместить
          </button>
        </div>
      </div>
    </div>

    <!-- Lightbox -->
    <FsLightbox
      :sources="lightboxSources"
      :type="lightboxType"
      :index="lightboxIndex"
      :toggler="lightboxToggler"
      @close="handleLightboxClose"
    />
  </div>
</template>

<script>
    import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { apiGet, apiPost, apiDelete, apiPut } from '../utils/api'
import { useAuthToken } from '../composables/useAuthToken'
import FsLightbox from 'fslightbox-vue'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

const API_BASE = '/api/v1'

export default {
  name: 'Media',
  components: {
    FsLightbox
  },
  props: {
    selectionMode: {
      type: Boolean,
      default: false
    },
    countFile: {
      type: Number,
      default: 1
    },
    selectedFiles: {
      type: Array,
      default: () => []
    }
  },
  emits: ['file-selected'],
  setup(props, { emit }) {
    console.log('[Media] setup() вызван')
    
    const router = useRouter()
    const loading = ref(false)
    const error = ref(null)
    const folders = ref([])
    const files = ref([])
    const selectedFolder = ref(null)
    const searchQuery = ref('')
    const pagination = ref(null)
    const uploading = ref(false)
    const showCreateFolderModal = ref(false)
    const newFolderName = ref('')
    const showMoveModal = ref(false)
    const fileToMove = ref(null)
    const moveTargetFolder = ref(null)
    const allFolders = ref([])
    
    // Lightbox
    const lightboxSources = ref([])
    const lightboxType = ref('image')
    const lightboxIndex = ref(0)
    const lightboxToggler = ref(false)
    
    // Selected files
    const selectedFileIds = ref(new Set())
    
    // Filtered folders
    const filteredFolders = computed(() => {
      if (!searchQuery.value) {
        return folders.value
      }
      const query = searchQuery.value.toLowerCase()
      return folders.value.filter(folder =>
        folder.name.toLowerCase().includes(query)
      )
    })
    
    // Load folders
    const loadFolders = async () => {
      loading.value = true
      error.value = null
      try {
        const response = await apiGet(`${API_BASE}/folders/tree/all`)
        folders.value = response.data || response
      } catch (err) {
        error.value = 'Ошибка загрузки папок'
        console.error('Error loading folders:', err)
      } finally {
        loading.value = false
      }
    }
    
    // Load files
    const loadFiles = async (page = 1) => {
      loading.value = true
      error.value = null
      try {
        const params = {
          per_page: 24,
          page: page,
          sort_by: 'created_at',
          sort_order: 'desc'
        }
        if (selectedFolder.value) {
          params.folder_id = selectedFolder.value.id
        }
        const response = await apiGet(`${API_BASE}/media`, { params })
        if (response.data) {
          files.value = response.data
          pagination.value = response.meta || response.pagination || null
        } else {
          files.value = response
          pagination.value = null
        }
      } catch (err) {
        error.value = 'Ошибка загрузки файлов'
        console.error('Error loading files:', err)
      } finally {
        loading.value = false
      }
    }
    
    // Handle folder click
    const handleFolderClick = (folder) => {
      selectedFolder.value = folder
      loadFiles()
    }
    
    // Handle back
    const handleBack = () => {
      selectedFolder.value = null
      loadFiles()
    }
    
    // Handle file click
    const handleFileClick = (file) => {
      if (props.selectionMode) {
        toggleFileSelection(file)
        return
      }
      
      if (file.type === 'photo') {
        const url = getFileUrl(file)
        lightboxSources.value = [url]
        lightboxType.value = 'image'
        lightboxIndex.value = 0
        lightboxToggler.value = !lightboxToggler.value
      }
    }
    
    // Handle file select
    const handleFileSelect = (event) => {
      const selectedFiles = Array.from(event.target.files)
      uploadFiles(selectedFiles)
      event.target.value = ''
    }
    
    // Handle drop
    const handleDrop = (event) => {
      const droppedFiles = Array.from(event.dataTransfer.files)
      uploadFiles(droppedFiles)
    }
    
    // Upload files
    const uploadFiles = async (filesToUpload) => {
      uploading.value = true
      error.value = null
      try {
        const formData = new FormData()
        filesToUpload.forEach((file) => {
          formData.append('files[]', file)
        })
        if (selectedFolder.value) {
          formData.append('folder_id', selectedFolder.value.id)
        }
        await apiPost(`${API_BASE}/media`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        await loadFiles(pagination.value?.current_page || 1)
        Swal.fire({
          icon: 'success',
          title: 'Файлы загружены',
          showConfirmButton: false,
          timer: 1500
        })
      } catch (err) {
        error.value = 'Ошибка загрузки файлов'
        console.error('Error uploading files:', err)
        Swal.fire({
          icon: 'error',
          title: 'Ошибка',
          text: 'Не удалось загрузить файлы'
        })
      } finally {
        uploading.value = false
      }
    }
    
    // Handle delete file
    const handleDeleteFile = async (file) => {
      const result = await Swal.fire({
        title: 'Удалить файл?',
        text: 'Файл будет перемещен в корзину',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Удалить',
        cancelButtonText: 'Отмена'
      })
      if (result.isConfirmed) {
        try {
          await apiDelete(`${API_BASE}/media/${file.id}`)
          await loadFiles(pagination.value?.current_page || 1)
          Swal.fire({
            icon: 'success',
            title: 'Файл удален',
            showConfirmButton: false,
            timer: 1500
          })
        } catch (err) {
          console.error('Error deleting file:', err)
          Swal.fire({
            icon: 'error',
            title: 'Ошибка',
            text: 'Не удалось удалить файл'
          })
        }
      }
    }
    
    // Handle delete folder
    const handleDeleteFolder = async (folder) => {
      const result = await Swal.fire({
        title: 'Удалить папку?',
        text: 'Папка будет перемещена в корзину',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Удалить',
        cancelButtonText: 'Отмена'
      })
      if (result.isConfirmed) {
        try {
          await apiDelete(`${API_BASE}/folders/${folder.id}`)
          await loadFolders()
          Swal.fire({
            icon: 'success',
            title: 'Папка удалена',
            showConfirmButton: false,
            timer: 1500
          })
        } catch (err) {
          console.error('Error deleting folder:', err)
          Swal.fire({
            icon: 'error',
            title: 'Ошибка',
            text: 'Не удалось удалить папку'
          })
        }
      }
    }
    
    // Handle create folder
    const handleToggleCreateFolder = () => {
      showCreateFolderModal.value = true
      newFolderName.value = ''
    }
    
    const handleCreateFolder = async () => {
      if (!newFolderName.value.trim()) return
      loading.value = true
      try {
        await apiPost(`${API_BASE}/folders`, {
          name: newFolderName.value.trim()
        })
        await loadFolders()
        showCreateFolderModal.value = false
        newFolderName.value = ''
        Swal.fire({
          icon: 'success',
          title: 'Папка создана',
          showConfirmButton: false,
          timer: 1500
        })
      } catch (err) {
        console.error('Error creating folder:', err)
        Swal.fire({
          icon: 'error',
          title: 'Ошибка',
          text: 'Не удалось создать папку'
        })
      } finally {
        loading.value = false
      }
    }
    
    // Handle move file
    const handleMoveFile = async (file) => {
      fileToMove.value = file
      await fetchAllFolders()
      showMoveModal.value = true
    }
    
    const handleSelectMoveFolder = (folder) => {
      moveTargetFolder.value = folder
    }
    
    const handleConfirmMove = async () => {
      if (!fileToMove.value || !moveTargetFolder.value) return
      try {
        await apiPut(`${API_BASE}/media/${fileToMove.value.id}`, {
          folder_id: moveTargetFolder.value.id
        })
        await loadFiles(pagination.value?.current_page || 1)
        showMoveModal.value = false
        fileToMove.value = null
        moveTargetFolder.value = null
        Swal.fire({
          icon: 'success',
          title: 'Файл перемещен',
          showConfirmButton: false,
          timer: 1500
        })
      } catch (err) {
        console.error('Error moving file:', err)
        Swal.fire({
          icon: 'error',
          title: 'Ошибка',
          text: 'Не удалось переместить файл'
        })
      }
    }
    
    // Get file URL
    const getFileUrl = (file) => {
      if (file.path) {
        return file.path.startsWith('http') ? file.path : `/${file.path}`
      }
      const uploadPath = 'upload'
      const folderPath = file.folder_path ? `${file.folder_path}/` : ''
      return `/${uploadPath}/${folderPath}${file.name}`
    }
    
    // Get folder icon
    const getFolderIcon = (folder) => {
      if (folder.src && folder.src !== 'folder.png') {
        return folder.src.startsWith('http') ? folder.src : `/${folder.src}`
      }
      return '/img/system/media/folder.png'
    }
    
    // Get file type icon
    const getFileTypeIcon = (type) => {
      const icons = {
        video: '/img/system/media/video.png',
        document: '/img/system/media/document.png',
        music: '/img/system/media/music.png'
      }
      return icons[type] || '/img/system/media/no-image.png'
    }
    
    // Handle folder image error
    const handleFolderImageError = (event) => {
      event.target.src = '/img/system/media/folder.png'
    }
    
    // Handle file image error
    const handleFileImageError = (event) => {
      event.target.src = '/img/system/media/no-image.png'
    }
    
    // Format file size
    const formatFileSize = (bytes) => {
      if (!bytes) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    }
    
    // Load page
    const loadPage = (page) => {
      loadFiles(page)
    }
    
    // Handle lightbox close
    const handleLightboxClose = () => {
      lightboxSources.value = []
    }
    
    // Toggle file selection
    const toggleFileSelection = (file) => {
      if (selectedFileIds.value.has(file.id)) {
        selectedFileIds.value.delete(file.id)
      } else {
        if (props.selectionMode && selectedFileIds.value.size >= props.countFile) {
          return
        }
        selectedFileIds.value.add(file.id)
      }
      const selectedFilesArray = files.value.filter(f => selectedFileIds.value.has(f.id))
      emit('file-selected', selectedFilesArray)
    }
    
    // Check if file is selected
    const isFileSelected = (file) => {
      return selectedFileIds.value.has(file.id)
    }
    
    // Download file
    const downloadFile = async (file) => {
      try {
        const url = getFileUrl(file)
        const link = document.createElement('a')
        link.href = url
        link.download = file.original_name
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
      } catch (err) {
        console.error('Error downloading file:', err)
      }
    }

    // Редактировать файл (только для фото)
    const handleEditFile = (file) => {
      if (file.type !== 'photo') {
        return
      }
      
      // Простая и надежная навигация - используем window.location.href напрямую
      // Это гарантирует работу без ошибок в консоли, даже если роут не найден
      window.location.href = `/admin/media/${file.id}/edit`
    }


    // Загрузить все папки для выбора
    const fetchAllFolders = async () => {
      try {
        const response = await apiGet(`${API_BASE}/folders/tree/all`)
        allFolders.value = response.data || response
      } catch (err) {
        console.error('Error loading all folders:', err)
      }
    }
    
    // Lifecycle
    onMounted(() => {
      loadFolders()
      loadFiles()
    })
    
    return {
      loading,
      error,
      folders,
      files,
      selectedFolder,
      searchQuery,
      pagination,
      uploading,
      showCreateFolderModal,
      newFolderName,
      showMoveModal,
      fileToMove,
      moveTargetFolder,
      allFolders,
      lightboxSources,
      lightboxType,
      lightboxIndex,
      lightboxToggler,
      filteredFolders,
      handleFolderClick,
      handleBack,
      handleFileClick,
      handleFileSelect,
      handleDrop,
      handleDeleteFile,
      handleDeleteFolder,
      handleToggleCreateFolder,
      handleCreateFolder,
      handleMoveFile,
      handleSelectMoveFolder,
      handleConfirmMove,
      getFileUrl,
      getFolderIcon,
      getFileTypeIcon,
      handleFolderImageError,
      handleFileImageError,
      formatFileSize,
      loadPage,
      handleLightboxClose,
      toggleFileSelection,
      isFileSelected,
      downloadFile,
      handleEditFile,
      fetchAllFolders
    }
  }
}
</script>
