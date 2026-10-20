const {
  withAppBuildGradle,
  withAndroidManifest,
} = require("expo/config-plugins");
module.exports = function withReleaseSigning(config) {
  config = withAndroidManifest(config, (mod) => {
    const application = mod.modResults.manifest.application[0].$;
    application["android:allowBackup"] = "false";
    application["android:usesCleartextTraffic"] =
      process.env.GULHOUSE_ANDROID_ALLOW_HTTP === "1" ? "true" : "false";
    return mod;
  });
  return withAppBuildGradle(config, (mod) => {
    if (
      !mod.modResults.contents.includes("// GulHouse private release signing")
    )
      mod.modResults.contents += `
// GulHouse private release signing. Keystore and password are never committed.
// Hash long generated C++ object paths to fit Windows' toolchain path limits.
android { defaultConfig { externalNativeBuild { cmake { arguments '-DCMAKE_OBJECT_PATH_MAX=240' } } } }
def ghStore = System.getenv('GULHOUSE_ANDROID_KEYSTORE')
if (ghStore) {
    android {
        signingConfigs {
            gulhouseRelease {
                storeFile file(ghStore)
                storePassword System.getenv('GULHOUSE_ANDROID_KEYSTORE_PASSWORD')
                keyAlias 'gulhouse'
                keyPassword System.getenv('GULHOUSE_ANDROID_KEYSTORE_PASSWORD')
            }
        }
        buildTypes { release { signingConfig signingConfigs.gulhouseRelease } }
    }
} else if (!System.getenv('EAS_BUILD') && gradle.startParameter.taskNames.any { it.toLowerCase().contains('release') }) {
    throw new GradleException('Use scripts/build-gulhouse-android.ps1 to provide private release signing.')
}
`;
    return mod;
  });
};
