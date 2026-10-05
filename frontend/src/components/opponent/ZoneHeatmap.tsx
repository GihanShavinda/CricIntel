import type {
  CSSProperties,
} from 'react';

import type {
  BatterAnalytics,
} from '../../types/opponentAnalytics';

export function ZoneHeatmap({
  zones,
}: {
  zones: BatterAnalytics['preferred_scoring_zones'];
}) {
  if (zones.length === 0) {
    return (
      <div className="opponent-empty">
        No pitch-zone data is recorded for this sample.
      </div>
    );
  }

  const maxRuns = Math.max(
    ...zones.map((zone) => zone.runs),
    1,
  );

  return (
    <div className="zone-heatmap">
      {zones.map((zone) => {
        const intensity = Math.max(
          0.12,
          Math.min(1, zone.runs / maxRuns),
        );

        return (
          <article
            key={zone.zone}
            style={{
              '--zone-intensity': String(intensity),
            } as CSSProperties}
          >
            <strong>{zone.zone}</strong>
            <span>{zone.runs} runs</span>
            <span>{zone.balls} balls</span>
            <span>SR {zone.strike_rate ?? '—'}</span>
          </article>
        );
      })}
    </div>
  );
}
