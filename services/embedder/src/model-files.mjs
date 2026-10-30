import path from 'node:path';

import { MODEL_ID } from './levels.mjs';

export const MODELS_DIR = process.env.EMBEDDER_MODELS_DIR || '/models';

// dtype q8 loads onnx/model_quantized.onnx in transformers.js.
export const MODEL_FILES = [
	'config.json',
	'tokenizer.json',
	'tokenizer_config.json',
	'special_tokens_map.json',
	'onnx/model_quantized.onnx',
];

export function modelDir( root = MODELS_DIR ) {
	return path.join( root, MODEL_ID );
}
