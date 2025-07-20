<template>
  <div id="app">
    <h2>Scan Barcode</h2>
    
    <div class="scanner">
      <StreamBarcodeReader 
        v-if="!scanned"
        @decode="onScan" 
        @loaded="ready = true"
      />
      
      <div v-if="scanned" class="success">
        <div class="check">✓</div>
        <p>{{ message }}</p>
        <p v-if="countdown > 0">Membuka dalam {{ countdown }} detik...</p>
      </div>
    </div>

    <div v-if="result && !isURL" class="result">
      <h3>Hasil: {{ result }}</h3>
    </div>

    <div v-if="result && isURL" class="result">
      <h3>URL: {{ result }}</h3>
    </div>

    <div class="buttons">
      <button v-if="scanned" @click="reset">Scan Lagi</button>
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
      result: null,
      scanned: false,
      ready: false,
      isURL: false,
      countdown: 0,
      message: 'Scan berhasil!'
    }
  },
  methods: {
    onScan(code) {
      this.scanned = true
      this.result = code
      this.isURL = code.includes('http') || code.includes('www.')
      
      if (this.isURL) {
        this.message = 'URL ditemukan!'
        this.autoOpen()
      }

      fetch('/barcode', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ kode: code })
      })
    },
    
    autoOpen() {
      this.countdown = 3
      let timer = setInterval(() => {
        this.countdown--
        if (this.countdown === 0) {
          clearInterval(timer)
          let url = this.result
          if (!url.startsWith('http')) {
            url = 'https://' + url
          }
          window.open(url, '_blank')
          setTimeout(() => this.reset(), 2000)
        }
      }, 1000)
    },
    
    reset() {
      this.scanned = false
      this.result = null
      this.isURL = false
      this.countdown = 0
    }
  }
}
</script>

<style>
#app {
  text-align: center;
  padding: 20px;
  max-width: 500px;
  margin: 0 auto;
}

.scanner {
  border: 2px solid #ccc;
  border-radius: 10px;
  margin: 20px 0;
  position: relative;
  overflow: hidden;
}

.success {
  background: rgba(0,0,0,0.8);
  color: white;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.check {
  font-size: 50px;
  color: green;
  background: white;
  border-radius: 50%;
  width: 70px;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 10px;
}

.result {
  margin: 20px 0;
  padding: 15px;
  background: #f5f5f5;
  border-radius: 8px;
}

.buttons {
  display: flex;
  gap: 10px;
  justify-content: center;
}

button {
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
  background: #007bff;
  color: white;
}

button:hover {
  opacity: 0.8;
}
</style>
