import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { PipelineStageView } from '../components/PipelineStageView';
import { OutputViewer } from '../components/OutputViewer';
import { TaskDetails, TaskOutputs } from '../types/api';
import { apiClient } from '../api/client';
import { JobStatus } from '../contracts';

export function TaskDetailsPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [task, setTask] = useState<TaskDetails | null>(null);
  const [outputs, setOutputs] = useState<TaskOutputs>({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [ticking, setTicking] = useState(false);
  const [retrying, setRetrying] = useState(false);

  const loadTaskDetails = async () => {
    if (!id) return;

    try {
      setLoading(true);
      setError(null);
      const [taskDetails, taskOutputs] = await Promise.all([
        apiClient.getTaskDetails(id),
        apiClient.getTaskOutputs(id).catch(() => ({ outputs: {} })),
      ]);
      setTask(taskDetails);
      setOutputs(taskOutputs.outputs);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load task details');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadTaskDetails();
  }, [id]);

  const handleTick = async () => {
    if (!id) return;

    try {
      setTicking(true);
      setError(null);
      await apiClient.tickTask(id);
      // Reload task details after ticking
      await loadTaskDetails();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to tick task');
    } finally {
      setTicking(false);
    }
  };

  const handleRetry = async (allowDlqOverride: boolean = false) => {
    if (!id) return;

    try {
      setRetrying(true);
      setError(null);
      await apiClient.retryJob(id, allowDlqOverride, 'Manual retry from UI');
      // Reload task details after retry
      await loadTaskDetails();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to retry task');
    } finally {
      setRetrying(false);
    }
  };

  const handleBack = () => {
    navigate('/tasks');
  };

  if (loading) {
    return (
      <div style={{ padding: '24px', maxWidth: '1400px', margin: '0 auto' }}>
        <div style={{ padding: '24px', textAlign: 'center' }}>Loading task details...</div>
      </div>
    );
  }

  if (!task) {
    return (
      <div style={{ padding: '24px', maxWidth: '1400px', margin: '0 auto' }}>
        <div style={{ padding: '24px', textAlign: 'center', color: '#dc3545' }}>
          Task not found
        </div>
      </div>
    );
  }

  const isInDLQ = task.stages.some((s) => s.status === JobStatus.DEAD_LETTER);
  const canTick = task.status === 'open' || task.status === 'running';
  const canRetry = task.status === 'failed' || isInDLQ;

  return (
    <div style={{ padding: '24px', maxWidth: '1400px', margin: '0 auto' }}>
      <div
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          marginBottom: '24px',
        }}
      >
        <div>
          <button
            onClick={handleBack}
            style={{
              padding: '6px 12px',
              backgroundColor: '#6c757d',
              color: '#fff',
              border: 'none',
              borderRadius: '4px',
              cursor: 'pointer',
              marginRight: '16px',
            }}
          >
            ← Back
          </button>
          <span style={{ fontSize: '24px', fontWeight: 'bold' }}>Task Details</span>
        </div>
        <div style={{ display: 'flex', gap: '8px' }}>
          <button
            onClick={handleTick}
            disabled={!canTick || ticking}
            style={{
              padding: '10px 20px',
              backgroundColor: !canTick || ticking ? '#6c757d' : '#0dcaf0',
              color: '#fff',
              border: 'none',
              borderRadius: '4px',
              cursor: !canTick || ticking ? 'not-allowed' : 'pointer',
              fontSize: '14px',
              fontWeight: 'bold',
            }}
          >
            {ticking ? 'Ticking...' : 'Tick Task'}
          </button>
          <button
            onClick={() => handleRetry(false)}
            disabled={!canRetry || retrying}
            style={{
              padding: '10px 20px',
              backgroundColor: !canRetry || retrying ? '#6c757d' : '#ffc107',
              color: '#000',
              border: 'none',
              borderRadius: '4px',
              cursor: !canRetry || retrying ? 'not-allowed' : 'pointer',
              fontSize: '14px',
              fontWeight: 'bold',
            }}
          >
            {retrying ? 'Retrying...' : 'Retry'}
          </button>
          {isInDLQ && (
            <button
              onClick={() => handleRetry(true)}
              disabled={retrying}
              style={{
                padding: '10px 20px',
                backgroundColor: retrying ? '#6c757d' : '#dc3545',
                color: '#fff',
                border: 'none',
                borderRadius: '4px',
                cursor: retrying ? 'not-allowed' : 'pointer',
                fontSize: '14px',
                fontWeight: 'bold',
              }}
            >
              {retrying ? 'Retrying...' : 'Retry from DLQ (Admin)'}
            </button>
          )}
        </div>
      </div>

      {error && (
        <div
          style={{
            padding: '12px',
            marginBottom: '16px',
            backgroundColor: '#f8d7da',
            color: '#721c24',
            border: '1px solid #f5c6cb',
            borderRadius: '4px',
          }}
        >
          {error}
        </div>
      )}

      <div
        style={{
          backgroundColor: '#f8f9fa',
          padding: '16px',
          borderRadius: '4px',
          marginBottom: '24px',
        }}
      >
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px' }}>
          <div>
            <div style={{ fontSize: '12px', color: '#666', marginBottom: '4px' }}>Task ID</div>
            <div style={{ fontFamily: 'monospace', fontSize: '14px' }}>{task.id}</div>
          </div>
          <div>
            <div style={{ fontSize: '12px', color: '#666', marginBottom: '4px' }}>Status</div>
            <div>
              <span
                style={{
                  padding: '4px 8px',
                  borderRadius: '4px',
                  fontSize: '12px',
                  fontWeight: 'bold',
                  backgroundColor: getStatusColor(task.status),
                  color: '#fff',
                }}
              >
                {task.status}
              </span>
            </div>
          </div>
          <div>
            <div style={{ fontSize: '12px', color: '#666', marginBottom: '4px' }}>
              Current Stage
            </div>
            <div style={{ fontSize: '14px' }}>{task.currentStage || 'N/A'}</div>
          </div>
        </div>
      </div>

      <h3 style={{ marginBottom: '16px' }}>Pipeline Stages</h3>
      <PipelineStageView stages={task.stages} />

      <OutputViewer outputs={outputs} />
    </div>
  );
}

function getStatusColor(status: string): string {
  switch (status) {
    case 'open':
      return '#6c757d';
    case 'running':
      return '#0dcaf0';
    case 'succeeded':
      return '#28a745';
    case 'failed':
      return '#dc3545';
    case 'cancelled':
      return '#6c757d';
    default:
      return '#6c757d';
  }
}
