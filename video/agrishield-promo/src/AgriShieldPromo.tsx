import type {ReactNode} from 'react';
import {AbsoluteFill, Audio, Img, interpolate, Sequence, staticFile, useCurrentFrame, useVideoConfig} from 'remotion';
import {AnimatedTitle, Brand, Caption, Eyebrow, ImageBackdrop, reveal, Scene, SignalOrb, StatusPill} from './components/Shared';
import {fontFamily, palette} from './theme';

const FPS = 30;

const durations = {
  opening: 180,
  challenge: 180,
  record: 210,
  diagnosis: 180,
  voice: 180,
  inputs: 225,
  equipment: 225,
  pathway: 210,
  finale: 210,
} as const;

const FloatingCard: React.FC<{
  children: ReactNode;
  x: number;
  y: number;
  delay: number;
  width?: number;
  dark?: boolean;
}> = ({children, x, y, delay, width = 310, dark = false}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const enter = reveal(frame, fps, delay);
  return (
    <div style={{position: 'absolute', left: x, top: y, width, padding: 24, border: `1px solid ${dark ? 'rgba(255,255,255,.17)' : '#D5DED5'}`, borderRadius: 26, background: dark ? 'rgba(255,255,255,.075)' : 'rgba(255,255,255,.92)', backdropFilter: 'blur(14px)', color: dark ? palette.white : palette.ink, boxShadow: '0 28px 70px rgba(5,30,20,.16)', opacity: enter, transform: `translateY(${(1 - enter) * 55}px) scale(${0.92 + enter * 0.08})`}}>
      {children}
    </div>
  );
};

const SceneOpening: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const title = reveal(frame, fps, 18);
  const lineWidth = interpolate(frame, [28, 118], [0, 760], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  return (
    <Scene duration={durations.opening} background={palette.forestDeep}>
      <ImageBackdrop src="assets/farmer-field.png" darken={0.28} focus="44% center" />
      <div style={{position: 'absolute', inset: 0, background: 'linear-gradient(90deg, rgba(9,39,28,.4), transparent 60%)'}} />
      <div style={{position: 'absolute', top: 70, left: 70}}><Brand light /></div>
      <div style={{position: 'absolute', left: 70, right: 70, bottom: 250}}>
        <Eyebrow light>Field signal 01</Eyebrow>
        <div style={{marginTop: 28, maxWidth: 860, color: palette.white, fontSize: 94, lineHeight: 0.93, fontWeight: 600, letterSpacing: -5, opacity: title, transform: `translateY(${(1 - title) * 70}px)`}}>
          Every season begins with a decision.
        </div>
        <div style={{marginTop: 34, width: lineWidth, height: 5, background: palette.millet}} />
      </div>
      <SignalOrb x={835} y={910} delay={35} size={26} />
      <Caption light>Every farming season begins with decisions—and access to the right resources.</Caption>
    </Scene>
  );
};

const SceneChallenge: React.FC = () => {
  const frame = useCurrentFrame();
  const drift = Math.sin(frame / 28) * 8;
  return (
    <Scene duration={durations.challenge} background={palette.forestDeep}>
      <div style={{position: 'absolute', inset: 0, background: 'radial-gradient(circle at 65% 34%, #214E39 0, #09271C 55%)'}} />
      <div style={{position: 'absolute', top: 82, left: 70}}><Eyebrow light>The access gap</Eyebrow></div>
      <div style={{position: 'absolute', left: 70, top: 160}}><AnimatedTitle light size={86}>The farm knows what it needs.</AnimatedTitle></div>
      <FloatingCard x={80} y={510 + drift} delay={18} dark>
        <div style={{fontSize: 18, color: palette.millet, fontWeight: 600}}>INPUTS</div>
        <div style={{fontSize: 34, marginTop: 12, fontWeight: 600}}>Seed + fertiliser</div>
        <div style={{height: 72, display: 'flex', gap: 12, alignItems: 'end', marginTop: 24}}>
          {[46, 68, 56, 72, 38].map((height, index) => <div key={index} style={{flex: 1, height, borderRadius: 8, background: index === 3 ? palette.millet : 'rgba(255,255,255,.22)'}} />)}
        </div>
      </FloatingCard>
      <FloatingCard x={520} y={455 - drift} delay={33} width={470} dark>
        <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center'}}><span style={{fontSize: 18, color: palette.millet, fontWeight: 600}}>EQUIPMENT</span><StatusPill tone="gold">Required this season</StatusPill></div>
        <div style={{marginTop: 24, height: 150, position: 'relative'}}>
          <div style={{position: 'absolute', left: 20, top: 62, width: 245, height: 58, borderRadius: 16, background: palette.leaf}} />
          <div style={{position: 'absolute', left: 215, top: 22, width: 96, height: 80, borderRadius: '12px 12px 0 0', border: `12px solid ${palette.leaf}`, borderBottom: 0}} />
          <div style={{position: 'absolute', left: 55, top: 105, width: 74, height: 74, borderRadius: '50%', background: palette.ink, border: `14px solid ${palette.muted}`}} />
          <div style={{position: 'absolute', left: 275, top: 104, width: 62, height: 62, borderRadius: '50%', background: palette.ink, border: `12px solid ${palette.muted}`}} />
        </div>
      </FloatingCard>
      <FloatingCard x={190} y={920 + drift * 0.5} delay={48} width={700} dark>
        <div style={{display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20}}>
          <div><span style={{fontSize: 17, color: '#A8C0B2'}}>NEEDED</span><div style={{fontSize: 31, marginTop: 9, fontWeight: 600}}>Irrigation pump</div></div>
          <div><span style={{fontSize: 17, color: '#A8C0B2'}}>BARRIER</span><div style={{fontSize: 31, marginTop: 9, fontWeight: 600}}>Upfront cost</div></div>
        </div>
      </FloatingCard>
      <Caption light>Quality inputs, suitable equipment and affordable financing can still be difficult to access.</Caption>
    </Scene>
  );
};

const FarmMap: React.FC = () => {
  const frame = useCurrentFrame();
  const progress = interpolate(frame, [15, 110], [0, 1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  return (
    <svg viewBox="0 0 640 590" style={{width: '100%', height: '100%'}}>
      <rect width="640" height="590" rx="30" fill="#164936" />
      <path d="M0 110 L640 40 M0 240 L640 170 M0 370 L640 300 M0 500 L640 430" stroke="#2E634C" strokeWidth="3" />
      <path d="M120 65 L265 85 L230 230 L75 205 Z" fill="#4C865B" />
      <path d="M290 98 L535 70 L578 260 L265 243 Z" fill="#2F7450" />
      <path d="M65 252 L226 266 L252 490 L105 532 L42 414 Z" fill="#6B8F57" />
      <path d="M278 280 L573 286 L532 522 L295 484 Z" fill="#4B7951" />
      <path d="M92 90 L526 77 L575 289 L527 512 L112 526 L52 392 Z" fill="none" stroke={palette.millet} strokeWidth="7" strokeDasharray="1500" strokeDashoffset={1500 * (1 - progress)} />
      <circle cx="334" cy="298" r={18 + Math.sin(frame / 8) * 3} fill={palette.millet} />
      <circle cx="334" cy="298" r="37" fill="none" stroke={palette.millet} opacity=".45" strokeWidth="3" />
    </svg>
  );
};

const SceneRecord: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const panel = reveal(frame, fps, 30);
  return (
    <Scene duration={durations.record}>
      <div style={{position: 'absolute', top: 74, left: 70}}><Brand /></div>
      <div style={{position: 'absolute', left: 70, top: 175}}><Eyebrow>One authorised record</Eyebrow><div style={{marginTop: 28}}><AnimatedTitle size={82} width={930}>Context turns signals into action.</AnimatedTitle></div></div>
      <div style={{position: 'absolute', left: 70, right: 70, top: 510, height: 830, display: 'grid', gridTemplateColumns: '1.18fr .82fr', gap: 22}}>
        <div style={{borderRadius: 34, overflow: 'hidden', boxShadow: '0 30px 80px rgba(18,59,42,.18)'}}><FarmMap /></div>
        <div style={{display: 'flex', flexDirection: 'column', gap: 18, opacity: panel, transform: `translateX(${(1 - panel) * 70}px)`}}>
          {[
            ['FARM', 'Garin Gona 04', 'Active record'],
            ['CROP SEASON', 'Maize · 4.8 ha', 'Week 07'],
            ['DATA STATUS', 'Signals connected', 'Updated today'],
            ['FIELD TEAM', 'Review assigned', 'Organisation scoped'],
          ].map(([label, value, meta], index) => (
            <div key={label} style={{flex: 1, padding: 25, borderRadius: 26, background: index === 0 ? palette.forest : palette.white, border: `1px solid ${index === 0 ? palette.forest : '#D2DCD2'}`, color: index === 0 ? palette.white : palette.ink}}>
              <div style={{fontSize: 15, color: index === 0 ? palette.millet : palette.leaf, letterSpacing: 2.4, fontWeight: 600}}>{label}</div>
              <div style={{fontSize: 28, marginTop: 15, fontWeight: 600}}>{value}</div>
              <div style={{fontSize: 17, marginTop: 7, color: index === 0 ? '#B9CDC0' : palette.muted}}>{meta}</div>
            </div>
          ))}
        </div>
      </div>
      <Caption>AgriShield connects each farm, crop season and field signal in one secure operational record.</Caption>
    </Scene>
  );
};

const ScanCorners: React.FC = () => {
  const frame = useCurrentFrame();
  const y = interpolate(frame % 80, [0, 79], [60, 470]);
  return (
    <div style={{position: 'absolute', inset: 28}}>
      {[['0','0','8px 0 0 0'], ['auto','0','0 8px 0 0'], ['0','auto','0 0 0 8px'], ['auto','auto','0 0 8px 0']].map(([left, top, radius], index) => (
        <div key={index} style={{position: 'absolute', left, right: left === 'auto' ? 0 : 'auto', top, bottom: top === 'auto' ? 0 : 'auto', width: 58, height: 58, borderLeft: left === '0' ? `5px solid ${palette.millet}` : 0, borderRight: left === 'auto' ? `5px solid ${palette.millet}` : 0, borderTop: top === '0' ? `5px solid ${palette.millet}` : 0, borderBottom: top === 'auto' ? `5px solid ${palette.millet}` : 0, borderRadius: radius}} />
      ))}
      <div style={{position: 'absolute', left: 12, right: 12, top: y, height: 3, background: `linear-gradient(90deg, transparent, ${palette.millet}, transparent)`, boxShadow: `0 0 20px ${palette.millet}`}} />
    </div>
  );
};

const SceneDiagnosis: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const result = reveal(frame, fps, 75);
  return (
    <Scene duration={durations.diagnosis} background="#E9EFE7">
      <div style={{position: 'absolute', top: 78, left: 70}}><Eyebrow>Field evidence</Eyebrow></div>
      <div style={{position: 'absolute', top: 160, left: 70}}><AnimatedTitle size={82}>A crop photo becomes a reviewable case.</AnimatedTitle></div>
      <div style={{position: 'absolute', left: 110, top: 490, width: 610, height: 900, padding: 18, borderRadius: 64, background: palette.forestDeep, boxShadow: '0 44px 95px rgba(9,39,28,.28)', transform: 'rotate(-2deg)'}}>
        <div style={{height: '100%', borderRadius: 48, overflow: 'hidden', position: 'relative', background: '#718B61'}}>
          <Img src={staticFile('assets/crop-evidence.webp')} style={{width: '100%', height: '100%', objectFit: 'cover'}} />
          <div style={{position: 'absolute', inset: 0, background: 'linear-gradient(180deg, rgba(9,39,28,.06), rgba(9,39,28,.4))'}} />
          <ScanCorners />
          <div style={{position: 'absolute', left: 28, right: 28, bottom: 32, padding: 20, borderRadius: 20, background: 'rgba(9,39,28,.84)', color: palette.white}}>
            <div style={{fontSize: 16, color: palette.millet, fontWeight: 600, letterSpacing: 2}}>IMAGE CAPTURED</div>
            <div style={{fontSize: 25, marginTop: 8, fontWeight: 600}}>Evidence linked to Garin Gona 04</div>
          </div>
        </div>
      </div>
      <div style={{position: 'absolute', right: 74, top: 720, width: 370, padding: 30, borderRadius: 30, background: palette.white, boxShadow: '0 30px 80px rgba(9,39,28,.16)', opacity: result, transform: `translateX(${(1 - result) * 80}px)`}}>
        <StatusPill tone="gold">AI screening</StatusPill>
        <div style={{marginTop: 26, fontSize: 17, color: palette.muted}}>POSSIBLE VISIBLE SIGN</div>
        <div style={{fontSize: 34, marginTop: 9, fontWeight: 600}}>Leaf stress pattern</div>
        <div style={{height: 1, background: '#DCE2D8', margin: '24px 0'}} />
        <div style={{fontSize: 17, color: palette.muted}}>NEXT STEP</div>
        <div style={{fontSize: 23, marginTop: 9, lineHeight: 1.35}}>Compare with field observations and request expert review.</div>
        <div style={{marginTop: 24, color: palette.clay, fontSize: 15, lineHeight: 1.4}}>Screening support—not a laboratory diagnosis.</div>
      </div>
      <Caption>Capture crop images and screen visible signs while keeping expert review in the workflow.</Caption>
    </Scene>
  );
};

const SceneVoice: React.FC = () => {
  const frame = useCurrentFrame();
  const heights = [28, 48, 84, 120, 74, 138, 92, 158, 104, 138, 70, 112, 58, 86, 42];
  const typed = 'Ganyen masarar ya fara canza launi...'.slice(0, Math.floor(interpolate(frame, [45, 125], [0, 39], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'})));
  return (
    <Scene duration={durations.voice} background={palette.forest}>
      <div style={{position: 'absolute', left: 70, top: 78}}><Eyebrow light>Field Voice · Hausa</Eyebrow></div>
      <div style={{position: 'absolute', left: 70, top: 160}}><AnimatedTitle light size={84}>Let the farmer speak first.</AnimatedTitle></div>
      <div style={{position: 'absolute', left: 70, right: 70, top: 550, height: 330, display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 18}}>
        {heights.map((height, index) => {
          const wave = 0.48 + Math.abs(Math.sin(frame / 7 + index * 0.72)) * 0.72;
          return <div key={index} style={{width: 36, height: height * wave, borderRadius: 20, background: index === 7 ? palette.millet : palette.rain, boxShadow: index === 7 ? `0 0 35px rgba(232,199,96,.4)` : 'none'}} />;
        })}
      </div>
      <div style={{position: 'absolute', left: 105, right: 105, top: 940, padding: 34, borderRadius: 34, background: palette.paper}}>
        <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center'}}><StatusPill tone="blue">Transcription</StatusPill><span style={{color: palette.muted, fontSize: 18}}>00:18</span></div>
        <div style={{minHeight: 120, marginTop: 30, fontSize: 34, lineHeight: 1.35, fontWeight: 500}}>{typed}<span style={{color: palette.millet}}>▍</span></div>
        <div style={{display: 'flex', gap: 12, marginTop: 18}}><StatusPill>Farm linked</StatusPill><StatusPill tone="gold">Review retained</StatusPill></div>
      </div>
      <Caption light>Farmers can report what they see by voice, in a familiar language, with the guidance linked to the farm.</Caption>
    </Scene>
  );
};

const SceneInputs: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const panel = reveal(frame, fps, 26);
  return (
    <Scene duration={durations.inputs} background={palette.forestDeep}>
      <ImageBackdrop src="assets/input-equipment-hub.png" darken={0.38} focus="50% center" zoom={1.03} />
      <div style={{position: 'absolute', top: 70, left: 70}}><Brand light /></div>
      <div style={{position: 'absolute', left: 70, top: 165}}><Eyebrow light>Input access</Eyebrow><div style={{marginTop: 24}}><AnimatedTitle light size={82}>Turn the farm record into a practical request.</AnimatedTitle></div></div>
      <div style={{position: 'absolute', left: 70, right: 70, bottom: 230, padding: 30, borderRadius: 38, background: 'rgba(246,248,241,.96)', boxShadow: '0 35px 90px rgba(0,0,0,.24)', opacity: panel, transform: `translateY(${(1 - panel) * 90}px)`}}>
        <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center'}}><div><div style={{fontSize: 17, color: palette.leaf, letterSpacing: 2.4, fontWeight: 600}}>SEASON PLAN · MAIZE</div><div style={{fontSize: 35, marginTop: 8, fontWeight: 600}}>Recommended input bundle</div></div><StatusPill tone="green">4.8 ha</StatusPill></div>
        <div style={{display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 14, marginTop: 28}}>
          {[
            ['01', 'Improved seed', 'Supplier request'],
            ['02', 'Fertiliser', 'Input financing'],
            ['03', 'Crop protection', 'Partner review'],
          ].map(([number, title, meta], index) => (
            <div key={title} style={{padding: 22, minHeight: 175, borderRadius: 24, background: index === 1 ? palette.forest : '#EDF2E9', color: index === 1 ? palette.white : palette.ink}}>
              <div style={{color: palette.millet, fontSize: 17, fontWeight: 600}}>{number}</div><div style={{fontSize: 26, marginTop: 38, fontWeight: 600}}>{title}</div><div style={{fontSize: 15, marginTop: 8, color: index === 1 ? '#B7C9BD' : palette.muted}}>{meta}</div>
            </div>
          ))}
        </div>
        <div style={{marginTop: 18, padding: '20px 24px', borderRadius: 18, background: palette.millet, display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: 24, fontWeight: 600}}><span>Prepare farm-linked request</span><span>→</span></div>
      </div>
      <Caption light>Explore available farm inputs and prepare a farm-linked request for participating suppliers or finance partners.</Caption>
    </Scene>
  );
};

const EquipmentIllustration: React.FC = () => {
  const frame = useCurrentFrame();
  const wheel = frame * 1.2;
  return (
    <div style={{position: 'relative', height: 330}}>
      <div style={{position: 'absolute', left: 80, top: 120, width: 500, height: 120, borderRadius: '24px 42px 22px 22px', background: palette.leaf}} />
      <div style={{position: 'absolute', left: 405, top: 35, width: 150, height: 150, borderRadius: '22px 22px 4px 4px', border: `22px solid ${palette.leaf}`, borderBottom: 0}} />
      <div style={{position: 'absolute', left: 110, top: 100, width: 155, height: 32, borderRadius: 12, background: palette.millet}} />
      <div style={{position: 'absolute', left: 148, top: 208, width: 155, height: 155, borderRadius: '50%', background: palette.ink, border: '24px solid #6F7C73', transform: `rotate(${wheel}deg)`}}><div style={{position: 'absolute', inset: 48, borderRadius: '50%', background: palette.millet}} /></div>
      <div style={{position: 'absolute', left: 470, top: 216, width: 115, height: 115, borderRadius: '50%', background: palette.ink, border: '20px solid #6F7C73', transform: `rotate(${wheel}deg)`}}><div style={{position: 'absolute', inset: 34, borderRadius: '50%', background: palette.millet}} /></div>
      <div style={{position: 'absolute', left: 14, top: 243, width: 650, height: 4, background: '#C9D4CA'}} />
    </div>
  );
};

const SceneEquipment: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const sheet = reveal(frame, fps, 35);
  const selected = frame > 128;
  return (
    <Scene duration={durations.equipment} background="#EEF2EA">
      <div style={{position: 'absolute', top: 76, left: 70}}><Eyebrow>Equipment pathways</Eyebrow></div>
      <div style={{position: 'absolute', left: 70, top: 160}}><AnimatedTitle size={82}>Own it, lease it, or request the service.</AnimatedTitle></div>
      <div style={{position: 'absolute', left: 70, right: 70, top: 500, padding: 36, borderRadius: 42, background: palette.white, boxShadow: '0 34px 90px rgba(18,59,42,.15)', opacity: sheet, transform: `translateY(${(1 - sheet) * 70}px)`}}>
        <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'start'}}><div><div style={{fontSize: 17, color: palette.leaf, fontWeight: 600, letterSpacing: 2}}>FIELD EQUIPMENT</div><div style={{fontSize: 42, marginTop: 10, fontWeight: 600}}>75 HP tractor</div></div><StatusPill tone="gold">Matched to farm profile</StatusPill></div>
        <EquipmentIllustration />
        <div style={{display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 14, marginTop: 32}}>
          {[
            ['LEASE', 'Seasonal access', 'Lower upfront requirement'],
            ['FINANCE', 'Purchase pathway', 'Partner eligibility review'],
            ['SERVICE', 'Pay per use', 'Book an equipment provider'],
          ].map(([label, title, meta], index) => (
            <div key={label} style={{minHeight: 195, padding: 22, borderRadius: 24, border: `2px solid ${selected && index === 0 ? palette.millet : '#D6DED4'}`, background: selected && index === 0 ? '#FFF8D9' : palette.paper, transform: selected && index === 0 ? 'translateY(-8px)' : 'none'}}>
              <div style={{fontSize: 15, color: palette.leaf, fontWeight: 600, letterSpacing: 2}}>{label}</div><div style={{fontSize: 25, marginTop: 34, fontWeight: 600}}>{title}</div><div style={{fontSize: 15, color: palette.muted, marginTop: 8, lineHeight: 1.35}}>{meta}</div>
            </div>
          ))}
        </div>
        <div style={{marginTop: 18, display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '20px 24px', borderRadius: 18, background: palette.forest, color: palette.white}}><span style={{fontSize: 23, fontWeight: 600}}>{selected ? 'Leasing pathway selected' : 'Compare access options'}</span><span style={{fontSize: 25, color: palette.millet}}>→</span></div>
      </div>
      <Caption>Access equipment through leasing, purchase financing or service-based options—subject to partner eligibility and approval.</Caption>
    </Scene>
  );
};

const ScenePathway: React.FC = () => {
  const frame = useCurrentFrame();
  const progress = interpolate(frame, [20, 145], [0, 1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  const steps = [
    ['01', 'Farm context', 'Boundary · crop · season'],
    ['02', 'Resource request', 'Inputs · equipment'],
    ['03', 'Partner review', 'Eligibility · availability'],
    ['04', 'Farmer decision', 'Terms reviewed before acceptance'],
  ];
  return (
    <Scene duration={durations.pathway} background={palette.forestDeep}>
      <div style={{position: 'absolute', top: 76, left: 70}}><Brand light /></div>
      <div style={{position: 'absolute', left: 70, top: 180}}><Eyebrow light>Responsible access</Eyebrow><div style={{marginTop: 28}}><AnimatedTitle light size={84}>A clear path. Clear responsibilities.</AnimatedTitle></div></div>
      <div style={{position: 'absolute', left: 95, top: 575, bottom: 280, width: 5, background: 'rgba(255,255,255,.14)'}}><div style={{width: '100%', height: `${progress * 100}%`, background: palette.millet}} /></div>
      <div style={{position: 'absolute', left: 145, right: 75, top: 555, display: 'flex', flexDirection: 'column', gap: 18}}>
        {steps.map(([number, title, meta], index) => {
          const show = reveal(frame, FPS, 15 + index * 26);
          return (
            <div key={number} style={{minHeight: 170, display: 'grid', gridTemplateColumns: '95px 1fr auto', alignItems: 'center', gap: 18, padding: '24px 28px', borderRadius: 28, background: 'rgba(255,255,255,.075)', border: '1px solid rgba(255,255,255,.14)', color: palette.white, opacity: show, transform: `translateX(${(1 - show) * 60}px)`}}>
              <div style={{fontSize: 23, color: palette.millet, fontWeight: 600}}>{number}</div><div><div style={{fontSize: 31, fontWeight: 600}}>{title}</div><div style={{fontSize: 18, marginTop: 6, color: '#AFC3B6'}}>{meta}</div></div><div style={{width: 42, height: 42, borderRadius: '50%', display: 'grid', placeItems: 'center', background: show > 0.96 ? palette.millet : 'rgba(255,255,255,.12)', color: palette.forestDeep, fontSize: 12, letterSpacing: 0.6, fontWeight: 600}}>OK</div>
            </div>
          );
        })}
      </div>
      <div style={{position: 'absolute', left: 150, right: 70, bottom: 185, color: '#B7C9BD', fontSize: 17, lineHeight: 1.4}}>AgriShield prepares and tracks the request. Financing, leasing, fulfilment and credit decisions remain with participating providers and regulated partners.</div>
      <Caption light>AgriShield supports the application journey while each provider remains responsible for approval and fulfilment.</Caption>
    </Scene>
  );
};

const SceneFinale: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();
  const brand = reveal(frame, fps, 35);
  const rule = interpolate(frame, [55, 130], [0, 700], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  return (
    <Scene duration={durations.finale} background={palette.forestDeep}>
      <ImageBackdrop src="assets/farm-outcome.png" darken={0.3} focus="50% center" zoom={1.02} />
      <div style={{position: 'absolute', inset: 0, background: 'linear-gradient(180deg, rgba(9,39,28,.1) 0%, rgba(9,39,28,.18) 42%, rgba(9,39,28,.88) 77%, #09271C 100%)'}} />
      <div style={{position: 'absolute', left: 70, right: 70, bottom: 230, opacity: brand, transform: `translateY(${(1 - brand) * 80}px)`}}>
        <Brand light />
        <div style={{marginTop: 34, maxWidth: 900, color: palette.white, fontSize: 88, fontWeight: 600, lineHeight: 0.95, letterSpacing: -4.5}}>Know what the farm needs. Find a path to access it.</div>
        <div style={{width: rule, height: 5, marginTop: 36, background: palette.millet}} />
        <div style={{marginTop: 30, display: 'flex', alignItems: 'center', justifyContent: 'space-between'}}>
          <div style={{color: '#D8E4DC', fontSize: 23}}>Field intelligence · Input access · Equipment opportunities</div>
          <div style={{padding: '18px 24px', borderRadius: 16, background: palette.millet, color: palette.forestDeep, fontSize: 20, fontWeight: 600}}>REQUEST A DEMONSTRATION →</div>
        </div>
      </div>
      <Caption light>AgriShield AI. From field intelligence to the resources needed for action.</Caption>
    </Scene>
  );
};

export const AgriShieldPromo: React.FC = () => {
  return (
    <AbsoluteFill style={{background: palette.forestDeep, fontFamily}}>
      <Sequence from={0} durationInFrames={durations.opening} premountFor={FPS}><SceneOpening /></Sequence>
      <Sequence from={180} durationInFrames={durations.challenge} premountFor={FPS}><SceneChallenge /></Sequence>
      <Sequence from={360} durationInFrames={durations.record} premountFor={FPS}><SceneRecord /></Sequence>
      <Sequence from={570} durationInFrames={durations.diagnosis} premountFor={FPS}><SceneDiagnosis /></Sequence>
      <Sequence from={750} durationInFrames={durations.voice} premountFor={FPS}><SceneVoice /></Sequence>
      <Sequence from={930} durationInFrames={durations.inputs} premountFor={FPS}><SceneInputs /></Sequence>
      <Sequence from={1155} durationInFrames={durations.equipment} premountFor={FPS}><SceneEquipment /></Sequence>
      <Sequence from={1380} durationInFrames={durations.pathway} premountFor={FPS}><ScenePathway /></Sequence>
      <Sequence from={1590} durationInFrames={durations.finale} premountFor={FPS}><SceneFinale /></Sequence>
      <Audio src={staticFile('audio/voiceover.m4a')} volume={1} />
      <Audio src={staticFile('audio/ambient.m4a')} volume={(f) => interpolate(f, [0, 45, 1650, 1799], [0, 0.12, 0.12, 0], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'})} />
    </AbsoluteFill>
  );
};

export const Poster: React.FC = () => (
  <AbsoluteFill style={{background: palette.forestDeep, fontFamily}}>
    <Img src={staticFile('assets/farmer-field.png')} style={{position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover', objectPosition: '44% center'}} />
    <div style={{position: 'absolute', inset: 0, background: 'linear-gradient(180deg, rgba(9,39,28,.1), rgba(9,39,28,.18) 42%, rgba(9,39,28,.94) 88%)'}} />
    <div style={{position: 'absolute', top: 76, left: 70}}><Brand light /></div>
    <div style={{position: 'absolute', left: 70, right: 70, bottom: 110}}>
      <Eyebrow light>From signal to action</Eyebrow>
      <div style={{marginTop: 28, color: palette.white, fontSize: 94, lineHeight: 0.92, letterSpacing: -5, fontWeight: 600}}>Know what the farm needs.</div>
      <div style={{marginTop: 30, width: 560, height: 6, background: palette.millet}} />
      <div style={{marginTop: 28, color: '#D8E4DC', fontSize: 27}}>Field intelligence · Input access · Equipment opportunities</div>
    </div>
  </AbsoluteFill>
);
