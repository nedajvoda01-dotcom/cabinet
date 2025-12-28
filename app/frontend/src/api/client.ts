import {
  ListTasksResponse,
  GetTaskDetailsResponse,
  GetTaskOutputsResponse,
  CreateTaskResponse,
  TickTaskResponse,
  RetryJobResponse,
  ApiError,
} from '../types/api';

const API_BASE_URL = '/api';

// For demo purposes, we use a fixed actor ID and key
// In production, these would come from authentication
const DEMO_ACTOR_ID = 'user:demo-user';
const DEMO_KEY_ID = 'demo-key';
// const DEMO_SECRET = 'demo-secret'; // TODO: Use for signature generation

class ApiClient {
  private baseUrl: string;

  constructor(baseUrl: string = API_BASE_URL) {
    this.baseUrl = baseUrl;
  }

  private async request<T>(
    method: string,
    path: string,
    body?: unknown
  ): Promise<T> {
    const url = `${this.baseUrl}${path}`;
    const nonce = `nonce-${Date.now()}-${Math.random()}`;
    const traceId = `trace-${Date.now()}`;

    // Build headers
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'x-actor-id': DEMO_ACTOR_ID,
      'x-nonce': nonce,
      'x-key-id': DEMO_KEY_ID,
      'x-trace-id': traceId,
      // TODO: Implement proper signature generation
      'x-signature': 'demo-signature',
    };

    const options: RequestInit = {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
    };

    const response = await fetch(url, options);

    if (!response.ok) {
      const error: ApiError = await response.json();
      throw new Error(error.error || `HTTP ${response.status}`);
    }

    return response.json();
  }

  // GET /tasks - List all tasks
  async listTasks(): Promise<ListTasksResponse> {
    return this.request<ListTasksResponse>('GET', '/tasks');
  }

  // GET /tasks/{id} - Get task details
  async getTaskDetails(taskId: string): Promise<GetTaskDetailsResponse> {
    return this.request<GetTaskDetailsResponse>('GET', `/tasks/${taskId}`);
  }

  // GET /tasks/{id}/outputs - Get task outputs
  async getTaskOutputs(taskId: string): Promise<GetTaskOutputsResponse> {
    return this.request<GetTaskOutputsResponse>('GET', `/tasks/${taskId}/outputs`);
  }

  // POST /tasks/create - Create a new task
  async createTask(idempotencyKey: string): Promise<CreateTaskResponse> {
    return this.request<CreateTaskResponse>('POST', '/tasks/create', {
      idempotencyKey,
    });
  }

  // POST /tasks/{id}/tick - Manually advance pipeline
  async tickTask(taskId: string): Promise<TickTaskResponse> {
    return this.request<TickTaskResponse>('POST', `/tasks/${taskId}/tick`);
  }

  // POST /admin/pipeline/retry - Retry a failed job
  async retryJob(
    taskId: string,
    allowDlqOverride: boolean = false,
    reason?: string
  ): Promise<RetryJobResponse> {
    return this.request<RetryJobResponse>('POST', '/admin/pipeline/retry', {
      taskId,
      allowDlqOverride,
      reason,
    });
  }
}

export const apiClient = new ApiClient();
