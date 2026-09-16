import type { LucideIcon } from 'lucide-react'
import {
  Award,
  Bell,
  BookOpen,
  Check,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
  CircleAlert,
  CircleCheck,
  CircleHelp,
  ClipboardList,
  Clock,
  ExternalLink,
  Eye,
  Globe,
  GraduationCap,
  Image,
  Info,
  LayoutDashboard,
  ListChecks,
  LoaderCircle,
  Lock,
  LogOut,
  Medal,
  Menu,
  MoreVertical,
  PanelLeft,
  PanelLeftClose,
  Pencil,
  Play,
  Plus,
  RefreshCw,
  Search,
  SearchX,
  Timer,
  Trash2,
  TriangleAlert,
  Trophy,
  User,
  Users,
  X,
} from 'lucide-react'
import { cn } from '@/utils/cn'

const icons = {
  add: Plus,
  award: Award,
  book: BookOpen,
  check: Check,
  check_circle: CircleCheck,
  chevron_left: ChevronLeft,
  chevron_right: ChevronRight,
  close: X,
  dashboard: LayoutDashboard,
  delete: Trash2,
  edit: Pencil,
  error: CircleAlert,
  first_page: ChevronsLeft,
  globe: Globe,
  group: Users,
  help_outline: CircleHelp,
  image: Image,
  info: Info,
  last_page: ChevronsRight,
  list: ClipboardList,
  lock: Lock,
  logout: LogOut,
  medal: Medal,
  menu: Menu,
  more_vert: MoreVertical,
  notifications: Bell,
  open_in_new: ExternalLink,
  panel_left: PanelLeft,
  panel_left_close: PanelLeftClose,
  person: User,
  play: Play,
  progress_activity: LoaderCircle,
  quiz: ListChecks,
  refresh: RefreshCw,
  school: GraduationCap,
  search: Search,
  search_off: SearchX,
  timer: Timer,
  schedule: Clock,
  trophy: Trophy,
  visibility: Eye,
  warning: TriangleAlert,
} satisfies Record<string, LucideIcon>

type IconName = keyof typeof icons

export function MsIcon({
  name,
  className,
  filled,
}: {
  name: string
  className?: string
  filled?: boolean
}) {
  const Icon = icons[name as IconName] ?? CircleHelp

  return (
    <Icon
      aria-hidden="true"
      strokeWidth={1.75}
      absoluteStrokeWidth
      fill={filled ? 'currentColor' : 'none'}
      className={cn('inline-block size-[1em] shrink-0 align-middle', className)}
    />
  )
}
