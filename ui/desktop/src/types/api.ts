import { JobStatus, PipelineStage, ErrorKind } from '../contracts';

// Task status enum - matches backend TaskStatus
export enum TaskStatus {
  OPEN = 'open',
  RUNNING = 'running',
  SUCCEEDED = 'succeeded',
  FAILED = 'failed',
  CANCELLED = 'cancelled',
}

// Task list item
export interface TaskListItem {
  id: string;
  status: TaskStatus;
  currentStage: PipelineStage | null;
  attempts: number;
}

// Stage state
export interface StageState {
  stage: PipelineStage;
  status: JobStatus;
  attempt: number;
  error?: ErrorKind;
}

// Task details
export interface TaskDetails {
  id: string;
  status: TaskStatus;
  currentStage: PipelineStage | null;
  stages: StageState[];
}

// Task output
export interface TaskOutput {
  payload: unknown;
  created_at: string;
}

// Task outputs map
export type TaskOutputs = Record<string, TaskOutput>;

// API response types
export interface ListTasksResponse {
  tasks: TaskListItem[];
}

export interface GetTaskDetailsResponse extends TaskDetails {}

export interface GetTaskOutputsResponse {
  outputs: TaskOutputs;
}

export interface CreateTaskResponse {
  taskId: string;
}

export interface TickTaskResponse {
  status: 'advanced' | 'done' | 'failed';
  completed_stage?: string;
  next_stage?: string;
  task_status?: string;
}

export interface RetryJobResponse {
  success: boolean;
}

export interface ApiError {
  error: string;
  code?: string;
}
