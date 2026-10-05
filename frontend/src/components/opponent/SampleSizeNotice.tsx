import type {
  SampleSize,
} from '../../types/opponentAnalytics';

export function SampleSizeNotice({
  sample,
}: {
  sample: SampleSize;
}) {
  return (
    <div className={`sample-size-notice ${sample.level}`}>
      <strong>{sample.balls} balls</strong>
      <span>{sample.message}</span>
    </div>
  );
}
