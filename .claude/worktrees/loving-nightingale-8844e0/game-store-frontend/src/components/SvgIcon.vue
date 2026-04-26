<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  icon: { type: String, required: true },
  size: { type: [String, Number], default: 24 },
})

const svgContent = ref('')

const loadSvg = async () => {
  try {
    const modules = import.meta.glob('../assets/icons/platforms/*.svg', { as: 'raw' })
    const module = modules[`../assets/icons/platforms/${props.icon}.svg`]
    if (module) svgContent.value = await module()
  } catch (e) {
    console.error('SvgIcon: failed to load', props.icon, e)
  }
}

watch(() => props.icon, loadSvg)
onMounted(loadSvg)
</script>

<template>
  <!-- v-html on a div injects the full <svg>...</svg> directly — no nested SVG -->
  <span
    class="svg-icon"
    :style="{ width: `${size}px`, height: `${size}px` }"
    v-html="svgContent"
  />
</template>

<style scoped>
.svg-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
/* The injected <svg> must fill the container */
.svg-icon :deep(svg) {
  width: 100%;
  height: 100%;
  display: block;
}
</style>
