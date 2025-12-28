import { TaskListItem } from '../types/api';

interface TaskTableProps {
  tasks: TaskListItem[];
  onOpenTask: (taskId: string) => void;
}

export function TaskTable({ tasks, onOpenTask }: TaskTableProps) {
  return (
    <table style={{ width: '100%', borderCollapse: 'collapse' }}>
      <thead>
        <tr style={{ borderBottom: '2px solid #ccc', textAlign: 'left' }}>
          <th style={{ padding: '8px' }}>Task ID</th>
          <th style={{ padding: '8px' }}>Status</th>
          <th style={{ padding: '8px' }}>Current Stage</th>
          <th style={{ padding: '8px' }}>Attempts</th>
          <th style={{ padding: '8px' }}>Actions</th>
        </tr>
      </thead>
      <tbody>
        {tasks.length === 0 ? (
          <tr>
            <td colSpan={5} style={{ padding: '16px', textAlign: 'center', color: '#666' }}>
              No tasks found. Create a new task to get started.
            </td>
          </tr>
        ) : (
          tasks.map((task) => (
            <tr key={task.id} style={{ borderBottom: '1px solid #eee' }}>
              <td style={{ padding: '8px', fontFamily: 'monospace', fontSize: '12px' }}>
                {task.id}
              </td>
              <td style={{ padding: '8px' }}>
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
              </td>
              <td style={{ padding: '8px' }}>{task.currentStage || '-'}</td>
              <td style={{ padding: '8px' }}>{task.attempts}</td>
              <td style={{ padding: '8px' }}>
                <button
                  onClick={() => onOpenTask(task.id)}
                  style={{
                    padding: '6px 12px',
                    backgroundColor: '#007bff',
                    color: '#fff',
                    border: 'none',
                    borderRadius: '4px',
                    cursor: 'pointer',
                  }}
                >
                  Open
                </button>
              </td>
            </tr>
          ))
        )}
      </tbody>
    </table>
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
