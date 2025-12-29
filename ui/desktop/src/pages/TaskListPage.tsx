import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { TaskTable } from '../components/TaskTable';
import { TaskListItem } from '../types/api';
import { apiClient } from '../api/client';

export function TaskListPage() {
  const [tasks, setTasks] = useState<TaskListItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [creating, setCreating] = useState(false);
  const navigate = useNavigate();

  const loadTasks = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await apiClient.listTasks();
      setTasks(response.tasks);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load tasks');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadTasks();
  }, []);

  const handleCreateTask = async () => {
    try {
      setCreating(true);
      setError(null);
      const idempotencyKey = `task-${Date.now()}-${Math.random()}`;
      const response = await apiClient.createTask(idempotencyKey);
      // Reload tasks after creation
      await loadTasks();
      // Navigate to the new task
      navigate(`/tasks/${response.taskId}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create task');
    } finally {
      setCreating(false);
    }
  };

  const handleOpenTask = (taskId: string) => {
    navigate(`/tasks/${taskId}`);
  };

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
        <h1 style={{ margin: 0 }}>Tasks</h1>
        <button
          onClick={handleCreateTask}
          disabled={creating}
          style={{
            padding: '10px 20px',
            backgroundColor: creating ? '#6c757d' : '#28a745',
            color: '#fff',
            border: 'none',
            borderRadius: '4px',
            cursor: creating ? 'not-allowed' : 'pointer',
            fontSize: '14px',
            fontWeight: 'bold',
          }}
        >
          {creating ? 'Creating...' : 'Create Task'}
        </button>
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

      {loading ? (
        <div style={{ padding: '24px', textAlign: 'center' }}>Loading tasks...</div>
      ) : (
        <TaskTable tasks={tasks} onOpenTask={handleOpenTask} />
      )}
    </div>
  );
}
