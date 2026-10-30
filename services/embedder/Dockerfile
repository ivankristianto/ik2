FROM node:24-slim AS deps
WORKDIR /app
COPY package.json package-lock.json ./
# Scripts stay off: onnxruntime-node's postinstall only fetches optional CUDA binaries.
# transformers.node.mjs inlines its own copy of onnxruntime-web, so the package
# itself (about 140 MB of wasm) is never loaded; the selfcheck below proves it.
RUN npm ci --omit=dev --ignore-scripts --no-audit --no-fund \
	&& ORT_ARCH="$(node -p process.arch)" \
	&& find node_modules/onnxruntime-node/bin -mindepth 2 -maxdepth 2 ! -name linux -exec rm -rf {} + \
	&& find node_modules/onnxruntime-node/bin -mindepth 3 -maxdepth 3 ! -name "$ORT_ARCH" -exec rm -rf {} + \
	&& ls node_modules/onnxruntime-node/bin/napi-v*/linux/"$ORT_ARCH"/*.node \
	&& rm -rf node_modules/onnxruntime-web \
	&& find node_modules -name '*.map' -delete \
	&& npm cache clean --force

# Its own stage so code changes don't download the model again.
FROM node:24-slim AS model
WORKDIR /app
COPY src/levels.mjs src/model-files.mjs src/fetch-model.mjs src/
RUN EMBEDDER_MODELS_DIR=/models node src/fetch-model.mjs

FROM node:24-slim
ENV NODE_ENV=production \
	EMBEDDER_MODELS_DIR=/models
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY --from=model /models /models
COPY package.json ./
COPY src ./src
USER node
RUN --network=none node src/selfcheck.mjs
CMD ["node", "src/run.mjs"]
