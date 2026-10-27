import type {CSSProperties, ReactNode} from 'react';
import {Img, interpolate, spring, staticFile, useCurrentFrame, useVideoConfig} from 'remotion';
import {fontFamily, palette} from '../theme';

export const safe = (value: number): number => Math.max(0, Math.min(1, value));

export const reveal = (frame: number, fps: number, delay = 0): number =>
  spring({frame: frame - delay, fps, config: {damping: 200}, durationInFrames: 0.8 * fps});

export const Scene: React.FC<{children: ReactNode; duration: number; background?: string}> = ({
  children,
  duration,
  background = palette.paper,
}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const opacity = interpolate(frame, [0, 0.45 * fps, duration - 0.45 * fps, duration], [0, 1, 1, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });

  return (
    <div style={{position: 'absolute', inset: 0, overflow: 'hidden', background, opacity, fontFamily, color: palette.ink}}>
      {children}
    </div>
  );
};

export const ImageBackdrop: React.FC<{
  src: string;
  darken?: number;
  focus?: string;
  zoom?: number;
}> = ({src, darken = 0.15, focus = 'center', zoom = 1.07}) => {
  const frame = useCurrentFrame();
  const {durationInFrames} = useVideoConfig();
  const scale = interpolate(frame, [0, durationInFrames], [zoom, zoom + 0.05], {extrapolateRight: 'clamp'});
  const y = interpolate(frame, [0, durationInFrames], [0, -20], {extrapolateRight: 'clamp'});

  return (
    <>
      <Img
        src={staticFile(src)}
        style={{position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover', objectPosition: focus, transform: `translateY(${y}px) scale(${scale})`}}
      />
      <div style={{position: 'absolute', inset: 0, background: `linear-gradient(180deg, rgba(9,39,28,${darken * 0.35}) 0%, rgba(9,39,28,${darken}) 48%, rgba(9,39,28,${Math.min(0.92, darken + 0.5)}) 100%)`}} />
    </>
  );
};

export const Brand: React.FC<{light?: boolean}> = ({light = false}) => (
  <div style={{display: 'flex', alignItems: 'center', gap: 14, color: light ? palette.white : palette.forest}}>
    <div style={{width: 44, height: 44, padding: 9, borderRadius: 14, background: light ? palette.millet : palette.forest}}>
      <Img src={staticFile('assets/agrishield-mark.svg')} style={{width: '100%', height: '100%'}} />
    </div>
    <div style={{fontSize: 24, fontWeight: 600, letterSpacing: -1}}>AgriShield <span style={{color: palette.millet}}>AI</span></div>
  </div>
);

export const Eyebrow: React.FC<{children: ReactNode; light?: boolean; style?: CSSProperties}> = ({children, light = false, style}) => (
  <div style={{display: 'flex', alignItems: 'center', gap: 14, color: light ? palette.millet : palette.leaf, fontSize: 18, fontWeight: 600, letterSpacing: 3.2, textTransform: 'uppercase', ...style}}>
    <span style={{display: 'block', width: 36, height: 3, background: 'currentColor'}} />
    {children}
  </div>
);

export const AnimatedTitle: React.FC<{children: ReactNode; delay?: number; light?: boolean; size?: number; width?: number}> = ({children, delay = 0, light = false, size = 88, width = 900}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const enter = reveal(frame, fps, delay);
  return (
    <div style={{maxWidth: width, fontSize: size, lineHeight: 0.93, letterSpacing: -4.5, fontWeight: 600, color: light ? palette.white : palette.ink, opacity: enter, transform: `translateY(${(1 - enter) * 60}px)`}}>
      {children}
    </div>
  );
};

export const Caption: React.FC<{children: ReactNode; light?: boolean}> = ({children, light = false}) => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  const enter = interpolate(frame, [0.3 * fps, 0.65 * fps], [0, 1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  const exit = interpolate(frame, [durationInFrames - 0.5 * fps, durationInFrames], [1, 0], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  return (
    <div style={{position: 'absolute', left: 70, right: 70, bottom: 72, display: 'flex', justifyContent: 'center', opacity: Math.min(enter, exit)}}>
      <div style={{maxWidth: 930, padding: '18px 26px', borderRadius: 18, background: light ? 'rgba(9,39,28,.88)' : 'rgba(246,248,241,.94)', boxShadow: '0 18px 50px rgba(0,0,0,.18)', color: light ? palette.white : palette.ink, fontSize: 30, lineHeight: 1.25, fontWeight: 500, textAlign: 'center'}}>
        {children}
      </div>
    </div>
  );
};

export const SignalOrb: React.FC<{x: number; y: number; delay?: number; size?: number}> = ({x, y, delay = 0, size = 22}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const pulse = 1 + Math.sin((frame - delay) / (fps * 0.16)) * 0.12;
  const enter = reveal(frame, fps, delay);
  return (
    <div style={{position: 'absolute', left: x, top: y, width: size, height: size, borderRadius: '50%', background: palette.millet, transform: `scale(${enter * pulse})`, boxShadow: `0 0 0 ${18 * enter}px rgba(232,199,96,.16), 0 0 42px rgba(232,199,96,.8)`}} />
  );
};

export const StatusPill: React.FC<{children: ReactNode; tone?: 'green' | 'gold' | 'blue' | 'red'}> = ({children, tone = 'green'}) => {
  const colors = {green: [palette.leafSoft, palette.forest], gold: ['#F7EAB3', '#66500C'], blue: ['#DCEBF0', '#315E6D'], red: ['#F1DDD8', '#863629']} as const;
  return <span style={{display: 'inline-flex', alignItems: 'center', padding: '9px 13px', borderRadius: 999, background: colors[tone][0], color: colors[tone][1], fontSize: 16, fontWeight: 600, letterSpacing: 0.4}}>{children}</span>;
};
