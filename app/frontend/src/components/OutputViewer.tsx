import { TaskOutputs } from '../types/api';
import { PipelineStage } from '../contracts';

interface OutputViewerProps {
  outputs: TaskOutputs;
}

export function OutputViewer({ outputs }: OutputViewerProps) {
  const stageOrder: PipelineStage[] = [
    PipelineStage.PARSE,
    PipelineStage.PHOTOS,
    PipelineStage.PUBLISH,
    PipelineStage.EXPORT,
    PipelineStage.CLEANUP,
  ];

  return (
    <div style={{ marginTop: '24px' }}>
      <h3 style={{ marginBottom: '16px' }}>Stage Outputs</h3>
      {stageOrder.map((stage) => {
        const output = outputs[stage];
        if (!output) return null;

        return (
          <div
            key={stage}
            style={{
              marginBottom: '16px',
              border: '1px solid #ddd',
              borderRadius: '4px',
              overflow: 'hidden',
            }}
          >
            <div
              style={{
                padding: '8px 12px',
                backgroundColor: '#f5f5f5',
                borderBottom: '1px solid #ddd',
                fontWeight: 'bold',
              }}
            >
              {stage.toUpperCase()}
            </div>
            <div
              style={{
                padding: '12px',
                backgroundColor: '#fff',
              }}
            >
              <pre
                style={{
                  margin: 0,
                  fontSize: '12px',
                  fontFamily: 'monospace',
                  overflow: 'auto',
                  maxHeight: '200px',
                }}
              >
                {JSON.stringify(output.payload, null, 2)}
              </pre>
              <div
                style={{
                  marginTop: '8px',
                  fontSize: '11px',
                  color: '#666',
                }}
              >
                Created: {output.created_at}
              </div>
            </div>
          </div>
        );
      })}
      {Object.keys(outputs).length === 0 && (
        <div style={{ padding: '16px', textAlign: 'center', color: '#666' }}>
          No outputs available yet.
        </div>
      )}
    </div>
  );
}
