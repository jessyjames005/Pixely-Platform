// Unit tests for the Tuleap Pinia store: JSON:API collection/resource flows
// and the platform-standard error -> tuleapStatus mapping.
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { apiClient, ApiClientError } from '@shared/services/apiClient'
import { useTuleapStore } from './tuleap.store'

vi.mock('@shared/services/apiClient', () => ({
  apiClient: {
    getResource: vi.fn(),
    getCollection: vi.fn(),
    postResource: vi.fn(),
    putResource: vi.fn(),
    delete: vi.fn(),
  },
  ApiClientError: class extends Error {
    constructor(
      public status: number,
      public code: string,
      public message: string,
      public errors: unknown[] = [],
    ) {
      super(message)
      this.name = 'ApiClientError'
    }
  },
}))

const mocked = () => vi.mocked(apiClient)

function encodeDocumentId(parts: string[]): string {
  const canonicalJson = JSON.stringify(parts).replace(/\//g, '\\/')
  return btoa(canonicalJson).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
}

describe('useTuleapStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mocked().getResource.mockReset()
    mocked().getCollection.mockReset()
    mocked().postResource.mockReset()
    mocked().putResource.mockReset()
    mocked().delete.mockReset()
  })

  it('fetches projects as JSON:API resources and signals a connected state', async () => {
    const project = { id: 'proj-12', type: 'tuleap-projects', label: 'Platform', shortname: 'platform' }
    mocked().getCollection.mockResolvedValueOnce({
      resources: [project],
      meta: { total: 1 },
      links: { self: '/api/v1/tuleap/projects' },
    } as never)

    const store = useTuleapStore()
    expect(store.loading.projects).toBe(false)

    await store.fetchProjects()

    expect(mocked().getCollection).toHaveBeenCalledWith('/tuleap/projects')
    expect(store.projects).toHaveLength(1)
    expect(store.projects[0].label).toBe('Platform')
    expect(store.tuleapStatus).toBe('connected')
    expect(store.loading.projects).toBe(false)
    expect(store.error).toBeNull()
  })

  it('marks the Tuleap status as unreachable on TULEAP_UNAVAILABLE', async () => {
    mocked().getCollection.mockRejectedValueOnce(
      new ApiClientError(503, 'TULEAP_UNAVAILABLE', 'Tuleap is unreachable.', [
        { code: 'TULEAP_UNAVAILABLE', detail: 'Tuleap is unreachable.' },
      ]),
    )

    const store = useTuleapStore()
    await store.fetchProjects()

    expect(store.projects).toEqual([])
    expect(store.tuleapStatus).toBe('unreachable')
    expect(store.error).toBeTruthy()
  })

  it('records a generic error without flipping to unreachable', async () => {
    mocked().getCollection.mockRejectedValueOnce(
      new ApiClientError(500, 'UNKNOWN_ERROR', 'Upstream errored.', [{ code: 'X', detail: 'Y' }]),
    )

    const store = useTuleapStore()
    await store.fetchProjects()

    expect(store.tuleapStatus).toBe('unknown')
    expect(store.error).toBeTruthy()
  })

  it('fetches the local team roster from the team/members collection', async () => {
    mocked().getCollection.mockResolvedValueOnce({
      resources: [{ id: 'm-1', type: 'tuleap-team-members', name: 'Analyst', tuleap_username: 'analyst' }],
      meta: { total: 1 },
    } as never)

    const store = useTuleapStore()
    await store.fetchMembers(null)

    expect(mocked().getCollection).toHaveBeenCalledWith('/team/members', { project_id: undefined })
    expect(store.members).toHaveLength(1)
    expect(store.members[0].name).toBe('Analyst')
  })

  it('persists a new team member and refetches the roster', async () => {
    mocked().postResource.mockResolvedValueOnce({
      id: 'm-2',
      type: 'tuleap-team-members',
      name: 'Tester',
      tuleap_username: 'tester',
    } as never)
    mocked().getCollection.mockResolvedValueOnce({ resources: [], meta: { total: 0 } } as never)

    const store = useTuleapStore()
    await store.addMember('Tester', 'tester', null)

    expect(mocked().postResource).toHaveBeenCalledWith(
      '/team/members',
      'tuleap-team-members',
      { name: 'Tester', tuleap_username: 'tester', project_id: null },
    )
    expect(store.members).toHaveLength(0)
  })

  it('deletes a team member (routing by decoded resource id) and refetches the roster', async () => {
    const memberId = encodeDocumentId(['tuleap-team-members', '45'])
    mocked().delete.mockResolvedValueOnce(undefined as never)
    mocked().getCollection.mockResolvedValueOnce({ resources: [], meta: { total: 0 } } as never)

    const store = useTuleapStore()
    await store.deleteMember(memberId, null)

    expect(mocked().delete).toHaveBeenCalledWith('/team/members/45')
    expect(store.members).toHaveLength(0)
  })

  it('fetches the Tuleap connection config (single resource)', async () => {
    mocked().getResource.mockResolvedValueOnce({
      type: 'tuleap-configs',
      id: 'current',
      tuleap_logged_in: true,
      tuleap_user_id: 'user-8',
    } as never)

    const store = useTuleapStore()
    await store.fetchAppConfig()

    expect(mocked().getResource).toHaveBeenCalledWith('/config')
    expect(store.appConfig?.tuleap_logged_in).toBe(true)
    expect(store.appConfig?.tuleap_user_id).toBe('user-8')
  })
})
