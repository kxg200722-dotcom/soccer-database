<script setup lang="ts">
import type { AdminMatchDetail, GroupType, MatchListItem, MatchStatus } from '../../types/live'
import { useTable } from '@/shared/composables/useTable'
import { ElMessage, ElMessageBox } from 'element-plus'
import { onMounted, ref } from 'vue'
import AdminHeader from '../../components/admin/AdminHeader.vue'
import MatchFormDialog from '../../components/admin/MatchFormDialog.vue'
import MatchSearchForm from '../../components/admin/MatchSearchForm.vue'
import MatchTable from '../../components/admin/MatchTable.vue'
import { deleteMatch, fetchAdminMatchDetail, fetchAdminMatches } from '../../services/liveAdminApi'

const {
  loading,
  list,
  searchForm,
  page,
  nextPage,
  load,
  search,
  reset,
} = useTable(fetchAdminMatches, {
  group_type: '' as GroupType | '',
  status: '' as MatchStatus | '',
  keyword: '',
})

const dialogVisible = ref(false)
const editingMatch = ref<AdminMatchDetail | null>(null)

function openCreate(): void {
  editingMatch.value = null
  dialogVisible.value = true
}

/** 编辑需完整详情（version + 阵容），先拉取再开弹窗 */
async function openEdit(row: MatchListItem): Promise<void> {
  try {
    editingMatch.value = await fetchAdminMatchDetail(row.id)
    dialogVisible.value = true
  }
  catch {
    // 拦截器已统一提示
  }
}

async function onRemove(row: MatchListItem): Promise<void> {
  try {
    await ElMessageBox.confirm(
      `确认删除「${row.home_team_name} vs ${row.away_team_name}」？`,
      '删除确认',
      { type: 'warning' },
    )
  }
  catch {
    return
  }
  try {
    await deleteMatch(row.id)
    ElMessage.success('已删除')
    await load()
  }
  catch {
    // 拦截器已统一提示（4002 状态不可删除等）
  }
}

function onSaved(): void {
  void load()
}

function prevPage(): void {
  if (page.value > 1) {
    page.value -= 1
    void load()
  }
}

function next(): void {
  if (nextPage.value) {
    page.value = nextPage.value
    void load()
  }
}

onMounted(() => void load())
</script>

<template>
  <div class="match-list-page">
    <AdminHeader />
    <div class="body">
      <div class="topbar">
        <MatchSearchForm
          v-model="searchForm"
          :loading="loading"
          @search="search"
          @reset="reset"
        />
        <el-button v-permission="'live:match:create'" type="primary" @click="openCreate">
          新建比赛
        </el-button>
      </div>

      <MatchTable :list="list" :loading="loading" @edit="openEdit" @remove="onRemove" />

      <div class="pager">
        <el-button size="small" :disabled="page <= 1 || loading" @click="prevPage">上一页</el-button>
        <span class="page-num">第 {{ page }} 页</span>
        <el-button size="small" :disabled="!nextPage || loading" @click="next">下一页</el-button>
      </div>

      <MatchFormDialog v-model="dialogVisible" :match="editingMatch" @saved="onSaved" />
    </div>
  </div>
</template>

<style scoped>
.body {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.pager {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 12px;
}

.page-num {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}
</style>
