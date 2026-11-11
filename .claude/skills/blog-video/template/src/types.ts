export type Cue = { text: string; say?: string; start?: number; end?: number };

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
	post: { slug: string; punch: string };
	scenes: Scene[];
};
