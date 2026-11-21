module.exports = ({ config }) => {
  const apiUrl = process.env.EXPO_PUBLIC_API_URL || '';
  const allowCleartextTraffic = process.env.EXPO_ALLOW_CLEARTEXT_HTTP === 'true' && apiUrl.startsWith('http://');

  return {
    ...config,
    plugins: (config.plugins || []).map((plugin) => {
      if (plugin === 'expo-build-properties') {
        return [
          'expo-build-properties',
          { android: { usesCleartextTraffic: allowCleartextTraffic } },
        ];
      }

      return plugin;
    }),
  };
};
