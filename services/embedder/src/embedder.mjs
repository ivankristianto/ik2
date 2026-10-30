// Imported only when a Run has work, so an empty queue never loads the model.
import { AutoModel, AutoTokenizer, env, mean_pooling } from '@huggingface/transformers';

import { DIMS, MODEL_DTYPE, MODEL_ID, inputFor, maxTokensFor } from './levels.mjs';
import { MODELS_DIR } from './model-files.mjs';

env.allowRemoteModels = false;
env.allowLocalModels = true;
env.localModelPath = MODELS_DIR;
env.useFSCache = false;

export async function loadEmbedder() {
	const tokenizer = await AutoTokenizer.from_pretrained( MODEL_ID );
	const model = await AutoModel.from_pretrained( MODEL_ID, {
		dtype: MODEL_DTYPE,
		device: 'cpu',
		// The arena keeps its high-water mark; off, peak RSS drops about 145 MB for about 8% more time.
		session_options: { enableCpuMemArena: false, enableMemPattern: false },
	} );

	/**
	 * Embeds one input, with the level's prefix and token cap.
	 * @return {Promise<Float32Array>} unit length, DIMS long
	 */
	async function embed( level, text ) {
		const inputs = tokenizer( inputFor( level, text ), {
			truncation: true,
			max_length: maxTokensFor( level ),
		} );
		const { last_hidden_state: hidden } = await model( inputs );
		const pooled = mean_pooling( hidden, inputs.attention_mask ).normalize( 2, -1 );
		const vector = Float32Array.from( pooled.data );
		for ( const t of [ hidden, pooled ] ) {
			t.dispose?.();
		}
		if ( vector.length !== DIMS ) {
			throw new Error( `Expected ${ DIMS } dimensions, got ${ vector.length }` );
		}
		if ( ! vector.every( Number.isFinite ) ) {
			throw new Error( 'Embedding has non-finite values' );
		}
		return vector;
	}

	function countTokens( text ) {
		return tokenizer.encode( text ).length;
	}

	return { embed, countTokens };
}
