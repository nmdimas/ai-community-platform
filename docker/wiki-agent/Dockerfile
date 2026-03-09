FROM node:20-alpine

WORKDIR /app

COPY apps/wiki-agent/package.json /app/package.json
RUN npm install

COPY apps/wiki-agent /app

EXPOSE 3000

CMD ["npm", "run", "start"]
