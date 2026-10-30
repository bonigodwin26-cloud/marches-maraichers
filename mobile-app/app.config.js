module.exports = ({ config }) => {
  const apiUrl = process.env.EXPO_PUBLIC_API_URL || '';
  const allowCleartextTraffic = apiUrl.startsWith('http://');

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
