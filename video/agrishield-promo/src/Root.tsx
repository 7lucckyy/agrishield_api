import {loadFont} from '@remotion/fonts';
import {Composition, Folder, Still, staticFile} from 'remotion';
import {AgriShieldPromo, Poster} from './AgriShieldPromo';

void Promise.all([
  loadFont({family: 'Instrument Sans', url: staticFile('fonts/instrument-sans-400.woff2'), weight: '400'}),
  loadFont({family: 'Instrument Sans', url: staticFile('fonts/instrument-sans-500.woff2'), weight: '500'}),
  loadFont({family: 'Instrument Sans', url: staticFile('fonts/instrument-sans-600.woff2'), weight: '600'}),
]);

export const RemotionRoot: React.FC = () => {
  return (
    <Folder name="AgriShield-Campaign">
      <Composition
        id="AgriShieldPromo"
        component={AgriShieldPromo}
        durationInFrames={1800}
        fps={30}
        width={1080}
        height={1920}
      />
      <Still id="AgriShieldPoster" component={Poster} width={1080} height={1920} />
    </Folder>
  );
};
