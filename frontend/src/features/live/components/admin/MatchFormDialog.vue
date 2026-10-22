<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import type { AdminMatchDetail, GroupType, LineupPlayer, MatchPayload } from '../../types/live'
import { useDict } from '@/shared/composables/useDict'
import { ElMessage } from 'element-plus'
import { reactive, ref, watch } from 'vue'
import { createMatch, updateMatch, updateMatchLineup } from '../../services/liveAdminApi'
import LineupEditor from './LineupEditor.vue'

const props = defineProps<{
  modelValue: boolean
  /** null=新建；编辑时需传完整详情（含 version 与阵容） */
  match: AdminMatchDetail | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  saved: [match: AdminMatchDetail]
}>()

const { dictOptions } = useDict('group_type')

const formRef = ref<FormInstance>()
const submitting = ref(false)

const emptyForm = (): MatchPayload => ({
  group_type: '' as GroupType,
  home_team_name: '',
  away_team_name: '',
  home_team_logo: null,
  away_team_logo: null,
  match_time: '',
  venue: null,
})

const form = reactive<MatchPayload>(emptyForm())
const lineupHome = ref<LineupPlayer[]>([])
const lineupAway = ref<LineupPlayer[]>([])
const lineupDirty = ref(false)

const rules: FormRules = {
  group_type: [{ required: true, message: '请选择组别', trigger: 'change' }],
  home_team_name: [{ required: true, message: '请输入主队名称', trigger: 'blur' }],
  away_team_name: [{ required: true, message: '请输入客队名称', trigger: 'blur' }],
  match_time: [{ required: true, message: '请选择比赛时间', trigger: 'change' }],
}

/** 弹窗重开：重置为初始数据，清空校验 */
watch(() => props.modelValue, (visible) => {
  if (!visible) return
  if (props.match) {
    Object.assign(form, {
      group_type: props.match.group_type,
      home_team_name: props.match.home_team_name,
      away_team_name: props.match.away_team_name,
      home_team_logo: props.match.home_team_logo,
      away_team_logo: props.match.away_team_logo,
      match_time: props.match.match_time ?? '',
      venue: props.match.venue,
    })
    lineupHome.value = [...(props.match.lineup_home ?? [])]
    lineupAway.value = [...(props.match.lineup_away ?? [])]
  }
  else {
    Object.assign(form, emptyForm())
    lineupHome.value = []
    lineupAway.value = []
  }
  lineupDirty.value = false
  formRef.value?.resetFields()
})

function close(): void {
  emit('update:modelValue', false)
}

async function submit(): Promise<void> {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return
  submitting.value = true
  try {
    let saved: AdminMatchDetail
    if (props.match) {
      saved = await updateMatch(props.match.id, { ...form, version: props.match.version })
      if (lineupDirty.value) {
        saved = await updateMatchLineup(props.match.id, {
          version: saved.version,
          lineup_home: lineupHome.value,
          lineup_away: lineupAway.value,
        })
      }
      ElMessage.success('保存成功')
    }
    else {
      saved = await createMatch(form)
      ElMessage.success('创建成功')
    }
    emit('saved', saved)
    close()
  }
  catch {
    // 拦截器已统一提示（10001/9/40002 等）
  }
  finally {
    submitting.value = false
  }
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="match ? '编辑比赛' : '新建比赛'"
    width="760px"
    @update:model-value="close"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
      <el-form-item label="组别" prop="group_type">
        <el-select v-model="form.group_type" placeholder="请选择组别" class="full">
          <el-option
            v-for="item in dictOptions.group_type"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="主队" prop="home_team_name">
        <el-input v-model="form.home_team_name" maxlength="100" placeholder="主队名称" />
      </el-form-item>
      <el-form-item label="客队" prop="away_team_name">
        <el-input v-model="form.away_team_name" maxlength="100" placeholder="客队名称" />
      </el-form-item>
      <el-form-item label="比赛时间" prop="match_time">
        <el-date-picker
          v-model="form.match_time"
          type="datetime"
          value-format="YYYY-MM-DD HH:mm:ss"
          placeholder="选择比赛时间"
          class="full"
        />
      </el-form-item>
      <el-form-item label="比赛地点" prop="venue">
        <el-input v-model="form.venue" maxlength="200" placeholder="选填" />
      </el-form-item>
    </el-form>

    <template v-if="match">
      <el-divider content-position="left">双方阵容</el-divider>
      <LineupEditor
        :home="lineupHome"
        :away="lineupAway"
        :home-team-name="form.home_team_name"
        :away-team-name="form.away_team_name"
        :disabled="match.status === 'finished'"
        @update:home="(v) => { lineupHome = v; lineupDirty = true }"
        @update:away="(v) => { lineupAway = v; lineupDirty = true }"
      />
    </template>
    <el-alert
      v-else
      type="info"
      :closable="false"
      title="保存后可在编辑中维护双方阵容"
    />

    <template #footer>
      <el-button @click="close">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="submit">确定</el-button>
    </template>
  </el-dialog>
</template>

<style scoped>
.full {
  width: 100%;
}
</style>
