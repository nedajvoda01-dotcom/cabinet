import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { TaskListPage } from '../pages/TaskListPage';
import { TaskDetailsPage } from '../pages/TaskDetailsPage';

export function App() {
  return (
    <Router>
      <div style={{ minHeight: '100vh', backgroundColor: '#f5f5f5' }}>
        <header
          style={{
            backgroundColor: '#2c3e50',
            color: '#fff',
            padding: '16px 24px',
            boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
          }}
        >
          <h1 style={{ margin: 0, fontSize: '20px' }}>Cabinet Control Panel</h1>
        </header>
        <Routes>
          <Route path="/" element={<Navigate to="/tasks" replace />} />
          <Route path="/tasks" element={<TaskListPage />} />
          <Route path="/tasks/:id" element={<TaskDetailsPage />} />
        </Routes>
      </div>
    </Router>
  );
}
