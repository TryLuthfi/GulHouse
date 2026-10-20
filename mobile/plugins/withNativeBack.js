const { withMainActivity } = require("expo/config-plugins");

// Register after ReactActivity's callback so Android 16 reaches the RN event
// directly, without routing through the deprecated Activity.onBackPressed shim.
// AndroidX keeps this callback scoped to the Activity lifecycle. RN Modal uses
// its own Dialog dispatcher and continues to call onRequestClose as before.
function applyNativeBack(contents) {
  if (contents.includes("// GulHouse AndroidX native back dispatcher")) {
    return contents;
  }
  const create = /super\.onCreate\((?:null|savedInstanceState)\)/;
  const fallback = /override fun invokeDefaultOnBackPressed\(\) \{[\s\S]*?\n  \}/;
  if (!create.test(contents) || !fallback.test(contents)) {
    throw new Error("GulHouse native back plugin: MainActivity template changed.");
  }
  return contents.replace(create, (original) => `${original}
    // GulHouse AndroidX native back dispatcher
    onBackPressedDispatcher.addCallback(this, object : androidx.activity.OnBackPressedCallback(true) {
      override fun handleOnBackPressed() {
        val handled = reactDelegate?.onBackPressed() == true
        android.util.Log.i("GH_BACK_NATIVE", "delegated=" + handled)
        if (!handled) invokeDefaultOnBackPressed()
      }
    })`).replace(fallback, `override fun invokeDefaultOnBackPressed() {
    // JS invokes this only when it has no deeper route to consume Back.
    // Retain the root task for a normal resume instead of finishing Activity.
    moveTaskToBack(false)
  }`);
}

module.exports = function withNativeBack(config) {
  return withMainActivity(config, (mod) => {
    if (mod.modResults.language !== "kt") {
      throw new Error("GulHouse native back plugin requires Kotlin MainActivity.");
    }
    mod.modResults.contents = applyNativeBack(mod.modResults.contents);
    return mod;
  });
};
module.exports.applyNativeBack = applyNativeBack;
