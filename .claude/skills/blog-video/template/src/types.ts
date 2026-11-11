export type Beat = 'and' | 'but' | 'mechanism' | 'therefore';
export type Focus = number | string;

export type Cue = {
	text: string;
	say?: string;
	beat?: Beat;
	/** Item or items of the scene's visual this sentence is about: they appear with it and stay green while it's spoken. */
	focus?: Focus | Focus[];
	start?: number;
	spoken?: number;
	end?: number;
	chunks?: { text: string; start: number; end: number }[];
};

export type Spoken = { text: string; say?: string; audio?: string; start?: number; end?: number };

export type StepState = 'normal' | 'skip' | 'break' | 'end';

export type Visual =
	| { type: 'steps'; steps: { label: string; note?: string; state?: StepState; at?: number }[] }
	| { type: 'diff'; file: string; lines: { text: string; kind?: 'ctx' | 'del' | 'add'; at?: number }[]; result?: string }
	| { type: 'receipt'; command: string; output: string[]; answer: string }
	| { type: 'rank'; rows: { label: string; score: number; at?: number }[]; threshold?: number; winnerAt?: number }
	| { type: 'token'; before?: string; mark: string; after?: string; caption?: string; size?: number };

export type Scene = {
	id: string;
	heading?: string;
	captions: Cue[];
	visual: Visual;
	audio?: string;
	seconds?: number;
	frames?: number;
};

export type Video = {
	orientation?: 'vertical' | 'horizontal';
	fps: number;
	introFrames: number;
	outroFrames: number;
	narrator?: { voice: string; lang: string; speed: number };
	post: { slug: string; punch: string; hook?: Spoken; pointer?: Spoken };
	scenes: Scene[];
};
