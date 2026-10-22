<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import type { EventType, MatchEventItem, TeamSide } from '../../types/live'
import { useDict } from '@/shared/composables/useDict'
import { reactive, ref, watch } from 'vue'

export interface EventFormValue {
  type: EventType
  minute: number
  team_side: TeamSide | null
  player_name: string | null
  player_no: number | null
  related_player_name: string | null
  content: string | null
}

const props = withDefaults(defineProps<{
  modelValue: boolean
  /** null=新增 */
  event: MatchEventItem | null
  submitting?: boolean
  homeTeamName?: string
  awayTeamName?: string
}>(), {
  submitting: false,
  homeTeamName: '主队',
  awayTeamName: '客队',
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  submit: [value: EventFormValue]
}>()

const { dictOptions } = useDict('event_type')

const formRef = ref<FormInstance>()

const emptyForm = (): EventFormValue => ({
  type: 'text',
  minute: 0,
  team_side: null,
  player_name: null,
  player_no: null,
  related_player_name: null,
  content: null,
})

const form = reactive<EventFormValue>(emptyForm())

/** 按 type 动态显隐字段（与后端 required_if 对齐） */
function needsTeamSide(type: EventType): boolean {
  return type !== 'text'
}

const rules: FormRules = {
  type: [{ required: true, message: '请选择类型', trigger: 'change' }],
  minute: [{ required: true, message: '请输入分钟', trigger: 'blur' }],
  team_side: [{
    validator: (_r, v, cb) => (needsTeamSide(form.type) && !v ? cb(new Error('请选择球队方')) : cb()),
    trigger: 'change',
  }],
  player_name: [{
    validator: (_r, v, cb) => (needsTeamSide(form.type) && !v ? cb(new Error('请输入球员姓名')) : cb()),
    trigger: 'blur',
  }],
  related_player_name: [{
    validator: (_r, v, cb) => (form.type === 'substitution' && !v ? cb(new Error('请输入换上球员')) : cb()),
    trigger: 'blur',
  }],
  content: [{
    validator: (_r, v, cb) => (form.type === 'text' && !v ? cb(new Error('请输入图文内容')) : cb()),
    trigger: 'blur',
  }],
}

/** 弹窗重开：重置为初始数据并清校验 */
watch(() => props.modelValue, (visible) => {
  if (!visible) return
  if (props.event) {
    Object.assign(form, {
      type: props.event.type,
      minute: props.event.minute,
      team_side: props.event.team_side,
      player_name: props.event.player_name,
      player_no: props.event.player_no,
      related_player_name: props.event.related_player_name,
      content: props.event.content,
    })
  }
  else {
    Object.assign(form, emptyForm())
  }
  formRef.value?.resetFields()
})

function close(): void {
  emit('update:modelValue', false)
}

async function onSubmit(): Promise<void> {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  emit('submit', { ...form })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="event ? '编辑事件' : '录入事件'"
    width="520px"
    @update:model-value="close"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
      <el-form-item label="类型" prop="type">
        <el-select v-model="form.type" class="full" :disabled="!!event">
          <el-option
            v-for="item in dictOptions.event_type"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="分钟" prop="minute">
        <el-input-number v-model="form.minute" :min="0" :max="130" />
      </el-form-item>

      <template v-if="form.type !== 'text'">
        <el-form-item label="球队方" prop="team_side">
          <el-radio-group v-model="form.team_side">
            <el-radio value="home">{{ homeTeamName }}</el-radio>
            <el-radio value="away">{{ awayTeamName }}</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="球员姓名" prop="player_name">
          <el-input v-model="form.player_name" maxlength="50" />
        </el-form-item>
        <el-form-item label="号码" prop="player_no">
          <el-input-number v-model="form.player_no" :min="0" :max="99" placeholder="选填" />
        </el-form-item>
        <el-form-item v-if="form.type === 'substitution'" label="换上球员" prop="related_player_name">
          <el-input v-model="form.related_player_name" maxlength="50" />
        </el-form-item>
        <el-form-item v-else-if="form.type === 'goal'" label="助攻" prop="related_player_name">
          <el-input v-model="form.related_player_name" maxlength="50" placeholder="选填" />
        </el-form-item>
      </template>

      <el-form-item v-if="form.type === 'text'" label="图文内容" prop="content">
        <el-input v-model="form.content" type="textarea" :rows="3" maxlength="500" show-word-limit />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="close">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="onSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>

<style scoped>
.full {
  width: 100%;
}
</style>
