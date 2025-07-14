<template>
  <div class="scanner-container">
    <h2>Scan Barcode Inventaris</h2>
    <div class="scanner-wrapper">
      <StreamBarcodeReader 
        @decode="handleScan" 
        @loaded="handleLoaded"
        @error="handleError"
      />
    </div>
    <div v-if="lastResult" class="result">
      <h3>Hasil Scan:</h3>
      <p>{{ lastResult }}</p>
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
      lastResult: null
    }
  },
  mounted() {
    console.log("Vue loaded")
  },
  methods: {
    handleScan(result) {
      console.log('Barcode scanned:', result)
      this.lastResult = result
      
      fetch('/barcode', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ kode: result })
      })
      .then(res => res.json())
      .then(data => {
        console.log('Respon Laravel:', data)
      })
      .catch(err => {
        console.error('Error sending to backend:', err)
      })
    },
    handleLoaded() {
      console.log('Camera loaded and ready to scan')
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
  margin: 2rem 0;
  border: 2px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
}

.result {
  margin-top: 2rem;
  padding: 1rem;
  background-color: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.result h3 {
  margin: 0 0 0.5rem 0;
  color: #28a745;
}

.result p {
  margin: 0;
  font-family: monospace;
  font-size: 1.1rem;
  color: #495057;
}
</style>