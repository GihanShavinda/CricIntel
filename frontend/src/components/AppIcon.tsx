import type {
  ReactNode,
  SVGProps,
} from 'react';

export type AppIconName =
  | 'activity'
  | 'analytics'
  | 'arrowRight'
  | 'building'
  | 'bell'
  | 'calendar'
  | 'chevronDown'
  | 'chevronLeft'
  | 'chevronRight'
  | 'clubs'
  | 'dashboard'
  | 'fixture'
  | 'logout'
  | 'menu'
  | 'organization'
  | 'players'
  | 'reports'
  | 'plus'
  | 'scouting'
  | 'search'
  | 'seasons'
  | 'settings'
  | 'strategy'
  | 'shield'
  | 'target'
  | 'teams'
  | 'training'
  | 'trophy'
  | 'user'
  | 'venues'
  | 'x';

interface AppIconProps extends SVGProps<SVGSVGElement> {
  name: AppIconName;
  size?: number;
}

const paths: Record<AppIconName, ReactNode> = {
  dashboard: (
    <>
      <rect x="3" y="3" width="7" height="7" rx="2" />
      <rect x="14" y="3" width="7" height="7" rx="2" />
      <rect x="3" y="14" width="7" height="7" rx="2" />
      <rect x="14" y="14" width="7" height="7" rx="2" />
    </>
  ),

  organization: (
    <>
      <path d="M4 21V7l8-4 8 4v14" />
      <path d="M9 21v-5h6v5" />
      <path d="M8 9h.01M12 9h.01M16 9h.01M8 12h.01M12 12h.01M16 12h.01" />
    </>
  ),

  bell: (
    <>
      <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
      <path d="M10 21h4" />
    </>
  ),

  building: (
    <>
      <path d="M3 21h18" />
      <path d="M6 21V5h9v16" />
      <path d="M15 9h3v12" />
      <path d="M9 8h3M9 12h3M9 16h3" />
    </>
  ),

  clubs: (
    <>
      <circle cx="9" cy="8" r="3" />
      <circle cx="17" cy="9" r="2.5" />
      <path d="M3 20c.5-4 2.8-6 6-6s5.5 2 6 6" />
      <path d="M14 15c3.5-.2 6 1.6 7 5" />
    </>
  ),

  teams: (
    <>
      <circle cx="8" cy="8" r="3" />
      <circle cx="16" cy="8" r="3" />
      <path d="M2.5 20c.7-4 2.7-6 5.5-6" />
      <path d="M21.5 20c-.7-4-2.7-6-5.5-6" />
      <path d="M8 20c.5-4 2.1-6 4-6s3.5 2 4 6" />
    </>
  ),

  players: (
    <>
      <circle cx="12" cy="8" r="4" />
      <path d="M4 21c.8-5 3.4-7 8-7s7.2 2 8 7" />
    </>
  ),

  seasons: (
    <>
      <rect x="3" y="5" width="18" height="16" rx="2" />
      <path d="M8 3v4M16 3v4M3 10h18" />
      <path d="M8 14h3M13 14h3M8 18h3" />
    </>
  ),

  venues: (
    <>
      <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" />
      <circle cx="12" cy="10" r="2.5" />
    </>
  ),

  trophy: (
    <>
      <path d="M8 4h8v4c0 4-1.7 7-4 7s-4-3-4-7V4Z" />
      <path d="M8 6H4c0 4 1.7 6 5 6M16 6h4c0 4-1.7 6-5 6" />
      <path d="M12 15v3M8 21h8M10 18h4" />
    </>
  ),

  fixture: (
    <>
      <rect x="3" y="5" width="18" height="16" rx="2" />
      <path d="M7 3v4M17 3v4M3 10h18" />
      <path d="m8 15 2 2 5-5" />
    </>
  ),

  analytics: (
    <>
      <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" />
      <path d="m4 8 5-4 6 5 5-4" />
    </>
  ),

  target: (
    <>
      <circle cx="12" cy="12" r="9" />
      <circle cx="12" cy="12" r="5" />
      <circle cx="12" cy="12" r="1.5" />
      <path d="M19 5 12 12" />
    </>
  ),

  strategy: (
    <>
      <rect x="3" y="4" width="18" height="16" rx="2" />
      <path d="M7 8h10M7 12h6M7 16h4" />
      <circle cx="17" cy="15" r="2.5" />
      <path d="m18.8 16.8 1.7 1.7" />
    </>
  ),

  training: (
    <>
      <path d="M5 8h3l8 8h3" />
      <path d="M5 16h3l8-8h3" />
      <path d="M3 6v4M21 6v4M3 14v4M21 14v4" />
    </>
  ),

  reports: (
    <>
      <path d="M6 3h9l3 3v15H6z" />
      <path d="M15 3v4h4" />
      <path d="M9 11h6M9 15h6M9 19h4" />
    </>
  ),

  scouting: (
    <>
      <circle cx="11" cy="11" r="7" />
      <path d="m16 16 5 5" />
      <path d="M8 11h6M11 8v6" />
    </>
  ),

  shield: (
    <>
      <path d="M12 3 20 6v5c0 5-3.2 8.5-8 10-4.8-1.5-8-5-8-10V6l8-3Z" />
      <path d="m9 12 2 2 4-5" />
    </>
  ),

  activity: (
    <>
      <path d="M3 12h4l2-6 4 12 2-6h6" />
    </>
  ),

  search: (
    <>
      <circle cx="11" cy="11" r="7" />
      <path d="m16 16 5 5" />
    </>
  ),

  plus: (
    <>
      <path d="M12 5v14M5 12h14" />
    </>
  ),

  user: (
    <>
      <circle cx="12" cy="8" r="4" />
      <path d="M4 21c.8-5 3.4-7 8-7s7.2 2 8 7" />
    </>
  ),

  logout: (
    <>
      <path d="M10 17l5-5-5-5" />
      <path d="M15 12H3" />
      <path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5" />
    </>
  ),

  settings: (
    <>
      <circle cx="12" cy="12" r="3" />
      <path d="M19 12a7 7 0 0 0-.1-1l2-1.6-2-3.4-2.4 1A8 8 0 0 0 15 6l-.4-2.6h-4L10 6a8 8 0 0 0-1.5 1L6 6 4 9.4 6 11a7 7 0 0 0 0 2l-2 1.6L6 18l2.4-1A8 8 0 0 0 10 18l.5 2.6h4L15 18a8 8 0 0 0 1.5-1l2.5 1 2-3.4-2-1.6c.1-.3.1-.7.1-1Z" />
    </>
  ),

  menu: (
    <>
      <path d="M4 7h16M4 12h16M4 17h16" />
    </>
  ),

  x: (
    <>
      <path d="m6 6 12 12M18 6 6 18" />
    </>
  ),

  chevronLeft: <path d="m15 18-6-6 6-6" />,
  chevronRight: <path d="m9 18 6-6-6-6" />,
  chevronDown: <path d="m6 9 6 6 6-6" />,

  arrowRight: (
    <>
      <path d="M5 12h14" />
      <path d="m14 7 5 5-5 5" />
    </>
  ),

  calendar: (
    <>
      <rect x="3" y="5" width="18" height="16" rx="2" />
      <path d="M8 3v4M16 3v4M3 10h18" />
    </>
  ),
};

export function AppIcon({
  name,
  size = 20,
  ...props
}: AppIconProps) {
  return (
    <svg
      aria-hidden="true"
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.8"
      strokeLinecap="round"
      strokeLinejoin="round"
      {...props}
    >
      {paths[name]}
    </svg>
  );
}
