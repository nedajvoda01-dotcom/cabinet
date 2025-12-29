import { PipelineStage, JobStatus } from '../contracts';
import { StageState } from '../types/api';

interface PipelineStageViewProps {
  stages: StageState[];
}

export function PipelineStageView({ stages }: PipelineStageViewProps) {
  const stageOrder: PipelineStage[] = [
    PipelineStage.PARSE,
    PipelineStage.PHOTOS,
    PipelineStage.PUBLISH,
    PipelineStage.EXPORT,
    PipelineStage.CLEANUP,
  ];

  // Create a map for easy lookup
  const stageMap = new Map(stages.map((s) => [s.stage, s]));

  return (
    <div style={{ display: 'flex', gap: '16px', marginTop: '16px' }}>
      {stageOrder.map((stage, index) => {
        const stageState = stageMap.get(stage);
        return (
          <div key={stage} style={{ flex: 1, position: 'relative' }}>
            <div
              style={{
                padding: '16px',
                border: '2px solid',
                borderColor: stageState ? getStatusBorderColor(stageState.status) : '#e0e0e0',
                borderRadius: '8px',
                backgroundColor: stageState ? getStatusBgColor(stageState.status) : '#f9f9f9',
              }}
            >
              <div
                style={{
                  fontSize: '12px',
                  fontWeight: 'bold',
                  marginBottom: '8px',
                  color: '#666',
                }}
              >
                {index + 1}. {stage.toUpperCase()}
              </div>
              <div
                style={{
                  fontSize: '14px',
                  fontWeight: 'bold',
                  marginBottom: '4px',
                }}
              >
                {stageState?.status || JobStatus.QUEUED}
              </div>
              {stageState && stageState.attempt > 0 && (
                <div style={{ fontSize: '12px', color: '#666' }}>
                  Attempt: {stageState.attempt}
                </div>
              )}
              {stageState?.error && (
                <div
                  style={{
                    fontSize: '12px',
                    color: '#dc3545',
                    marginTop: '4px',
                    fontWeight: 'bold',
                  }}
                >
                  Error: {stageState.error}
                </div>
              )}
            </div>
            {index < stageOrder.length - 1 && (
              <div
                style={{
                  position: 'absolute',
                  right: '-24px',
                  top: '50%',
                  transform: 'translateY(-50%)',
                  fontSize: '24px',
                  color: '#ccc',
                }}
              >
                →
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}

function getStatusBorderColor(status: JobStatus): string {
  switch (status) {
    case JobStatus.QUEUED:
      return '#6c757d';
    case JobStatus.RUNNING:
      return '#0dcaf0';
    case JobStatus.SUCCEEDED:
      return '#28a745';
    case JobStatus.FAILED:
      return '#dc3545';
    case JobStatus.DEAD_LETTER:
      return '#8b0000';
    default:
      return '#6c757d';
  }
}

function getStatusBgColor(status: JobStatus): string {
  switch (status) {
    case JobStatus.QUEUED:
      return '#f8f9fa';
    case JobStatus.RUNNING:
      return '#e7f6ff';
    case JobStatus.SUCCEEDED:
      return '#d4edda';
    case JobStatus.FAILED:
      return '#f8d7da';
    case JobStatus.DEAD_LETTER:
      return '#fdd';
    default:
      return '#f9f9f9';
  }
}
