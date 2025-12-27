export type PipelineStageEvent = {
  type: "pipeline.stage";
  correlation_id?: string;
  stage?: string;
  status?: string;
  progress?: number;
  error?: string | null;
};
