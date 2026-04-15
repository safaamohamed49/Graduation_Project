<script setup>
import { computed, ref, onMounted } from 'vue'
import FormControlIcon from '@/Components/FormControlIcon.vue'

const props = defineProps({
  name: String,
  id: String,
  autocomplete: String,
  maxlength: String,
  placeholder: String,
  inputmode: String,
  icon: String,
  options: Array,
  type: {
    type: String,
    default: 'text',
  },
  modelValue: {
    type: [String, Number, Boolean, Array, Object],
    default: '',
  },
  required: Boolean,
  borderless: Boolean,
  transparent: Boolean,
})

const emit = defineEmits(['update:modelValue', 'setRef'])

const computedValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const computedType = computed(() => (props.options ? 'select' : props.type))

const inputElClass = computed(() => {
  const base = [
    'w-full',
    'rounded-lg',
    'px-3',
    'py-2.5',
    'text-sm',
    'focus:outline-none',
    'focus:ring-2',
    'focus:ring-blue-200',
    'focus:border-blue-500',
    props.borderless ? 'border-0' : 'border border-gray-300',
    props.transparent ? 'bg-transparent' : 'bg-white',
  ]

  if (computedType.value === 'textarea') {
    base.push('min-h-[120px]')
  }

  if (props.icon) {
    base.push('pr-10')
  }

  return base
})

const controlIconH = computed(() =>
  props.type === 'textarea' ? 'h-full' : 'h-11'
)

const selectEl = ref(null)
const textareaEl = ref(null)
const inputEl = ref(null)

onMounted(() => {
  emit('setRef', selectEl.value || textareaEl.value || inputEl.value)
})
</script>

<template>
  <div class="relative">
    <select
      v-if="computedType === 'select'"
      :id="id"
      ref="selectEl"
      v-model="computedValue"
      :name="name"
      :class="inputElClass"
    >
      <option
        v-for="option in options"
        :key="option.id ?? option.value ?? option"
        :value="option.value ?? option"
      >
        {{ option.label ?? option.name ?? option }}
      </option>
    </select>

    <textarea
      v-else-if="computedType === 'textarea'"
      :id="id"
      ref="textareaEl"
      v-model="computedValue"
      :name="name"
      :maxlength="maxlength"
      :placeholder="placeholder"
      :required="required"
      :class="inputElClass"
    />

    <input
      v-else
      :id="id"
      ref="inputEl"
      v-model="computedValue"
      :name="name"
      :maxlength="maxlength"
      :inputmode="inputmode"
      :autocomplete="autocomplete"
      :required="required"
      :placeholder="placeholder"
      :type="computedType"
      :class="inputElClass"
    />

    <FormControlIcon v-if="icon" :icon="icon" :h="controlIconH" />
  </div>
</template>