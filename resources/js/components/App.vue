<template>
  <div class="scanner-container">
    <h2>Scan Barcode Inventaris</h2>
    
    <!-- Scanner Area -->
    <div class="scanner-wrapper" :class="{ 'frozen': isScanned }">
      <StreamBarcodeReader 
        v-if="!isScanned"
        @decode="handleScan" 
        @loaded="handleLoaded"
        @error="handleError"
        ref="scanner"
      />
      
      <!-- Frozen Frame Overlay -->
      <div v-if="isScanned" class="frozen-overlay">
        <div class="scan-success-indicator">
          <div class="checkmark-animation">
            <div class="checkmark">✓</div>
          </div>
          <p class="success-text">{{ successMessage }}</p>
          <div v-if="redirectCountdown > 0 && isUrl" class="countdown">
            Membuka URL dalam {{ redirectCountdown }} detik...
          </div>
        </div>
      </div>
    </div>

    <!-- Result Display -->
    <div v-if="lastResult && !isUrl" class="result">
      <h3>📦 Hasil Scan:</h3>
      <p class="result-text">{{ lastResult }}</p>
    </div>

    <!-- URL Result - Simplified Display -->
    <div v-if="lastResult && isUrl" class="result url-result">
      <h3>🔗 URL Terdeteksi - Sedang Membuka...</h3>
      <p class="result-text">{{ lastResult }}</p>
    </div>

    <!-- Control Buttons -->
    <div class="control-buttons">
      <button v-if="isScanned" @click="resetScan" class="reset-btn">
        🔄 Scan Lagi
      </button>
      <button v-if="!isScanned && cameraReady" @click="toggleTorch" class="torch-btn">
        {{ torchOn ? '🔦' : '💡' }} Flash
      </button>
    </div>
  </div>
</template>

<script>
import { StreamBarcodeReader } from 'vue-barcode-reader'

export default {
  name: 'App',
  components: {
    StreamBarcodeReader
  },
  data() {
    return {
      lastResult: null,
      isScanned: false,
      cameraReady: false,
      torchOn: false,
      isUrl: false,
      redirectCountdown: 0,
      redirectTimer: null,
      successMessage: 'Barcode Berhasil Di-scan!'
    }
  },
  mounted() {
    console.log("Vue loaded")
  },
  beforeUnmount() {
    // Clear timer saat component di-destroy
    if (this.redirectTimer) {
      clearInterval(this.redirectTimer)
    }
  },
  methods: {
    handleScan(result) {
      console.log('Barcode scanned:', result)
      
      // Freeze camera
      this.isScanned = true
      this.lastResult = result
      
      // Check if result is URL
      this.isUrl = this.checkIfUrl(result)
      
      // Update success message
      this.successMessage = this.isUrl ? 'URL Terdeteksi!' : 'Barcode Berhasil Di-scan!'
      
      // Send to Laravel backend
      fetch('/barcode', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ 
          kode: result,
          is_url: this.isUrl 
        })
      })
      .then(res => res.json())
      .then(data => {
        console.log('Respon Laravel:', data)
      })
      .catch(err => {
        console.error('Error sending to backend:', err)
      })
      
      // Auto redirect jika URL - langsung tanpa tombol
      if (this.isUrl) {
        this.startAutoRedirect()
      }
    },
    
    checkIfUrl(text) {
      // Check berbagai format URL
      const urlPattern = /^(https?:\/\/|www\.|ftp:\/\/)/i
      const domainPattern = /^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}/
      
      return urlPattern.test(text) || domainPattern.test(text)
    },
    
    startAutoRedirect() {
      this.redirectCountdown = 2 // Kurangi jadi 2 detik untuk lebih cepat
      
      this.redirectTimer = setInterval(() => {
        this.redirectCountdown--
        
        if (this.redirectCountdown <= 0) {
          clearInterval(this.redirectTimer)
          this.openUrl()
        }
      }, 1000)
    },
    
    openUrl() {
      if (this.redirectTimer) {
        clearInterval(this.redirectTimer)
      }
      
      let url = this.lastResult
      
      // Tambah https:// jika belum ada protocol
      if (!url.startsWith('http://') && !url.startsWith('https://')) {
        url = 'https://' + url
      }
      
      console.log('Attempting to open URL:', url)
      
      // Coba beberapa metode untuk membuka URL
      try {
        // Method 1: window.open dengan _blank
        const newWindow = window.open(url, '_blank', 'noopener,noreferrer')
        
        if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
          // Jika popup blocked, coba method 2
          console.log('Popup blocked, trying alternative method')
          
          // Method 2: Buat link temporary dan click
          const link = document.createElement('a')
          link.href = url
          link.target = '_blank'
          link.rel = 'noopener noreferrer'
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
        }
        
        // Method 3: Fallback - redirect di tab yang sama setelah delay
        setTimeout(() => {
          if (confirm('URL tidak bisa dibuka di tab baru. Buka di tab ini?')) {
            window.location.href = url
          }
        }, 1000)
        
      } catch (error) {
        console.error('Error opening URL:', error)
        // Fallback final - copy to clipboard dan alert
        if (navigator.clipboard) {
          navigator.clipboard.writeText(url).then(() => {
            alert(`URL disalin ke clipboard: ${url}`)
          })
        } else {
          alert(`Silakan copy URL ini manual: ${url}`)
        }
      }
      
      // Reset setelah 3 detik (lebih lama untuk memberikan waktu)
      setTimeout(() => {
        this.resetScan()
      }, 3000)
    },
    
    resetScan() {
      this.isScanned = false
      this.lastResult = null
      this.isUrl = false
      this.redirectCountdown = 0
      if (this.redirectTimer) {
        clearInterval(this.redirectTimer)
      }
    },
    
    toggleTorch() {
      // Note: Torch feature tergantung browser dan device support
      if (this.$refs.scanner && this.$refs.scanner.toggleTorch) {
        this.$refs.scanner.toggleTorch()
        this.torchOn = !this.torchOn
      }
    },
    
    handleLoaded() {
      console.log('Camera loaded and ready to scan')
      this.cameraReady = true
    },
    
    handleError(err) {
      console.error('Scan Error:', err)
    }
  }
}
</script>

<style scoped>
.scanner-container {
  padding: 2rem;
  max-width: 600px;
  margin: auto;
  text-align: center;
}

.scanner-wrapper {
  position: relative;
  margin: 2rem 0;
  border: 2px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.scanner-wrapper.frozen {
  border-color: #28a745;
  box-shadow: 0 0 20px rgba(40, 167, 69, 0.3);
}

.frozen-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.scan-success-indicator {
  text-align: center;
  color: white;
}

.checkmark-animation {
  animation: checkmark-bounce 0.6s ease-out;
}

.checkmark {
  font-size: 4rem;
  color: #28a745;
  background: white;
  border-radius: 50%;
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.success-text {
  font-size: 1.2rem;
  font-weight: bold;
  margin: 0;
}

.countdown {
  margin-top: 1rem;
  font-size: 1rem;
  color: #ffc107;
  font-weight: bold;
}

.result {
  margin-top: 2rem;
  padding: 1rem;
  background-color: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
  transition: all 0.3s ease;
}

.result.url-result {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: #667eea;
  animation: pulse 1s ease-in-out infinite alternate;
}

.result h3 {
  margin: 0 0 0.5rem 0;
  color: #28a745;
}

.result.url-result h3 {
  color: white;
}

.result-text {
  margin: 0;
  font-family: monospace;
  font-size: 1.1rem;
  color: #495057;
  word-break: break-all;
  padding: 0.5rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}

.result.url-result .result-text {
  color: white;
  background: rgba(255, 255, 255, 0.2);
}

.control-buttons {
  margin-top: 2rem;
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.reset-btn, .torch-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: bold;
  transition: all 0.3s ease;
}

.reset-btn {
  background: #007bff;
  color: white;
}

.reset-btn:hover {
  background: #0056b3;
  transform: translateY(-2px);
}

.torch-btn {
  background: #ffc107;
  color: #212529;
}

.torch-btn:hover {
  background: #e0a800;
  transform: translateY(-2px);
}

@keyframes checkmark-bounce {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.2);
    opacity: 1;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes pulse {
  from {
    box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
  }
  to {
    box-shadow: 0 0 20px rgba(102, 126, 234, 0.8);
  }
}

/* Responsive design */
@media (max-width: 480px) {
  .scanner-container {
    padding: 1rem;
  }
  
  .checkmark {
    width: 60px;
    height: 60px;
    font-size: 3rem;
  }
  
  .control-buttons {
    flex-direction: column;
    align-items: center;
  }
}
</style>