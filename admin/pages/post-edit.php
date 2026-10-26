<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('content');

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;

if ($id) {
    $st = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
    $post = $st->get_result()->fetch_assoc();
    if (!$post) { header('Location: posts.php'); exit; }
}

$pageTitle = $id ? 'Edit Post' : 'New Blog Post';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $title      = trim($_POST['title'] ?? '');
    $slug       = slugify(trim($_POST['slug'] ?? '') ?: $title);
    $excerpt    = trim($_POST['excerpt'] ?? '');
    $category   = trim($_POST['category'] ?? '');
    $author     = trim($_POST['author'] ?? '');
    $read_time  = trim($_POST['read_time'] ?? '');
    $content    = $_POST['content'] ?? '';
    $metaTitle  = mb_substr(plainText((string)($_POST['meta_title'] ?? '')), 0, 120);
    $metaDesc   = mb_substr(plainText((string)($_POST['meta_description'] ?? '')), 0, 320);
    $keyword    = mb_substr(plainText((string)($_POST['focus_keyword'] ?? '')), 0, 120);
    $noindex    = !empty($_POST['noindex']) ? 1 : 0;
    $excerpt    = plainText($excerpt);                       // HTML pasted into the excerpt would show as raw tags
    $status     = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
    if (!isset(BLOG_CATEGORIES[$category])) $category = 'Business Tips';
    if ($slug === '') $slug = 'post-' . date('YmdHis');
    $slug = mb_substr($slug, 0, 190);

    // Thumbnail upload
    $thumbnail = $post['thumbnail'] ?? '';
    if (!empty($_FILES['thumbnail']['name'])) {
        $uploadDir = __DIR__ . '/../../assets/img/blog/';
        $uploaded  = uploadImage($_FILES['thumbnail'], $uploadDir);
        if ($uploaded) $thumbnail = 'assets/img/blog/' . $uploaded;
    }

    if ($id) {
        $stmt = $conn->prepare("UPDATE blog_posts SET title=?,slug=?,excerpt=?,category=?,author=?,read_time=?,content=?,status=?,thumbnail=?,meta_title=?,meta_description=?,focus_keyword=?,noindex=?,updated_at=NOW() WHERE id=?");
        $stmt->bind_param('ssssssssssssii', $title, $slug, $excerpt, $category, $author, $read_time, $content, $status, $thumbnail, $metaTitle, $metaDesc, $keyword, $noindex, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO blog_posts (title,slug,excerpt,category,author,read_time,content,status,thumbnail,meta_title,meta_description,focus_keyword,noindex) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('ssssssssssssi', $title, $slug, $excerpt, $category, $author, $read_time, $content, $status, $thumbnail, $metaTitle, $metaDesc, $keyword, $noindex);
    }

    try {
        $stmt->execute();
        flashMessage('success', $id ? 'Post updated successfully.' : 'Post created successfully.');
        header('Location: posts.php');
        exit;
    } catch (mysqli_sql_exception $ex) {
        error_log('post save failed: ' . $ex->getMessage());
        flashMessage('error', $ex->getCode() === 1062 ? 'Another post already uses this URL slug — change the slug.' : 'Error saving post. Please try again.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') $post = array_merge($post ?? [], compact('title', 'slug', 'excerpt', 'category', 'author', 'read_time', 'status') + ['meta_title' => $metaTitle, 'meta_description' => $metaDesc, 'focus_keyword' => $keyword, 'noindex' => $noindex]);
include __DIR__ . '/../includes/admin-header.php';
?>

<?php renderFlash(); ?>

<div class="card">
    <div class="card-header">
        <h2><?php echo $pageTitle; ?></h2>
        <a href="posts.php" class="abtn abtn-outline abtn-sm">← Back</a>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Post Title *</label>
                    <input type="text" id="post_title" name="title" required value="<?php echo clean($post['title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>URL Slug *</label>
                    <input type="text" id="post_slug" name="slug" required value="<?php echo clean($post['slug'] ?? ''); ?>">
                    <span class="hint">Auto-generated from title. Only lowercase letters, numbers, hyphens.</span>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <?php foreach (array_keys(BLOG_CATEGORIES) as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo ($post['category'] ?? '') === $cat ? 'selected' : ''; ?>>
                            <?php echo $cat; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <select name="author">
                        <?php $names = array_column(authors(), 'name'); $cur = $post['author'] ?? ($names[0] ?? ''); if ($cur !== '' && !in_array($cur, $names, true)) $names[] = $cur; ?>
                        <?php foreach ($names as $n): ?>
                        <option value="<?php echo clean($n); ?>" <?php echo $cur === $n ? 'selected' : ''; ?>><?php echo clean($n); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Read Time</label>
                    <input type="text" name="read_time" placeholder="e.g. 5 min read" value="<?php echo clean($post['read_time'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft" <?php echo ($post['status'] ?? '') !== 'published' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Excerpt (short summary shown on blog list &amp; below the title) *</label>
                    <textarea name="excerpt" rows="3" required><?php echo clean($post['excerpt'] ?? ''); ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Thumbnail Image (JPG/PNG/WebP, max 5MB)</label>
                    <?php if (!empty($post['thumbnail'])): ?>
                        <p class="hint" style="margin-bottom:8px;">Current: <?php echo clean($post['thumbnail']); ?></p>
                    <?php endif; ?>
                    <input type="file" name="thumbnail" accept="image/*">
                </div>
                <div class="form-group full-width seo-box">
                    <h3 class="seo-title">SEO — how this post looks on Google</h3>
                    <div class="serp" aria-label="Google preview">
                        <div class="serp-url"><?php echo clean(SITE_URL); ?>/blog/<span id="serp_slug">your-slug</span></div>
                        <div class="serp-title" id="serp_title">Post title</div>
                        <div class="serp-desc" id="serp_desc">Description will appear here…</div>
                    </div>
                    <div class="form-grid" style="margin-top:14px;">
                        <div class="form-group full-width">
                            <label>Meta title <small id="cnt_title" class="cnt"></small></label>
                            <input type="text" id="meta_title" name="meta_title" maxlength="120" value="<?php echo clean($post['meta_title'] ?? ''); ?>" placeholder="Leave empty to use: Post title | Buildupnet">
                            <span class="hint">Best 30–60 characters. Put the main keyword near the start.</span>
                        </div>
                        <div class="form-group full-width">
                            <label>Meta description <small id="cnt_desc" class="cnt"></small></label>
                            <textarea id="meta_description" name="meta_description" rows="3" maxlength="320" placeholder="Leave empty to use the excerpt"><?php echo clean($post['meta_description'] ?? ''); ?></textarea>
                            <span class="hint">Best 120–158 characters. Plain text only (no HTML). Say what the reader will learn.</span>
                        </div>
                        <div class="form-group">
                            <label>Focus keyword</label>
                            <input type="text" id="focus_keyword" name="focus_keyword" maxlength="120" value="<?php echo clean($post['focus_keyword'] ?? ''); ?>" placeholder="e.g. website design cost in Punjab">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <label class="check-line"><input type="checkbox" name="noindex" value="1" <?php echo !empty($post['noindex']) ? 'checked' : ''; ?>> Hide this post from Google (noindex)</label>
                        </div>
                    </div>
                    <ul class="seo-checks" id="seo_checks"></ul>
                </div>
                <div class="form-group full-width">
                    <label>Post Content (HTML allowed) *</label>
                    <textarea name="content" rows="20" required style="font-family:monospace; font-size:0.85rem;"><?php echo htmlspecialchars($_POST['content'] ?? $post['content'] ?? ''); ?></textarea>
                    <span class="hint">Plain text is fine too (blank line = new paragraph). &lt;h1&gt; becomes &lt;h2&gt; automatically, inline styles, scripts and iframes are removed. Use &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;&lt;li&gt;, &lt;strong&gt;, &lt;a href&gt; tags. Full HTML supported.</span>
                </div>
            </div>
            <div class="form-actions" style="margin-top:20px;">
                <button type="submit" class="abtn abtn-primary"><?php echo $id ? 'Update Post' : 'Create Post'; ?></button>
                <a href="posts.php" class="abtn abtn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.seo-box{background:#f7fafc;border:1px solid var(--a-border);border-radius:10px;padding:16px}
.seo-title{margin:0 0 12px;font-size:1rem}
.serp{background:#fff;border:1px solid var(--a-border);border-radius:8px;padding:12px 14px;font-family:Arial,sans-serif}
.serp-url{color:#202124;font-size:.78rem;word-break:break-all}.serp-title{color:#1a0dab;font-size:1.15rem;line-height:1.3;margin:2px 0}
.serp-desc{color:#4d5156;font-size:.86rem;line-height:1.5}
.cnt{font-weight:400;color:var(--a-muted)}.cnt.ok{color:#1e8e3e}.cnt.warn{color:#c0392b}
.check-line{display:flex;align-items:center;gap:8px;min-height:42px;font-weight:500}
.seo-checks{list-style:none;margin:14px 0 0;padding:0;display:grid;gap:6px;font-size:.86rem}
.seo-checks li::before{content:"●";margin-right:8px}.seo-checks .ok::before{color:#1e8e3e}.seo-checks .bad::before{color:#c0392b}.seo-checks .mid::before{color:#e69500}
</style>
<script>
(function(){
  var $=function(id){return document.getElementById(id)};
  var f={title:$('post_title'),slug:$('post_slug'),mt:$('meta_title'),md:$('meta_description'),kw:$('focus_keyword'),ex:document.querySelector('[name=excerpt]'),ct:document.querySelector('[name=content]')};
  if(!f.mt) return;
  function plain(h){var d=document.createElement('div');d.innerHTML=h;return (d.textContent||'').replace(/\s+/g,' ').trim()}
  function cnt(el,id,min,max){var n=el.value.length,c=$(id);c.textContent=n+' chars';c.className='cnt '+(n>=min&&n<=max?'ok':(n?'warn':''))}
  function update(){
    var title=f.mt.value.trim()||(f.title.value.length<=46?f.title.value+' | Buildupnet':f.title.value);
    var desc=f.md.value.trim()||plain(f.ex.value);
    $('serp_title').textContent=title.length>60?title.slice(0,57)+'…':title;
    $('serp_desc').textContent=(desc.length>158?desc.slice(0,155)+'…':desc)||'Description will appear here…';
    $('serp_slug').textContent=f.slug.value||'your-slug';
    cnt(f.mt,'cnt_title',30,60);cnt(f.md,'cnt_desc',120,158);
    var k=f.kw.value.trim().toLowerCase(),body=plain(f.ct.value),words=body?body.split(' ').length:0,L=[];
    function add(c,t,m){L.push('<li class="'+(c?'ok':(m?'mid':'bad'))+'">'+t+'</li>')}
    add(f.mt.value.length>=30&&f.mt.value.length<=60,'Meta title is 30–60 characters (now '+f.mt.value.length+')',f.mt.value.length===0);
    add(f.md.value.length>=120&&f.md.value.length<=158,'Meta description is 120–158 characters (now '+f.md.value.length+')',f.md.value.length===0);
    add(words>=600,'Article length: '+words+' words (600+ recommended)',words>=300);
    var h2=(f.ct.value.match(/<h2/gi)||[]).length;add(h2>=2,'Has '+h2+' sub-heading(s) <h2> (2+ recommended)',h2===1);
    var imgs=f.ct.value.match(/<img\b[^>]*>/gi)||[],noalt=imgs.filter(function(i){return !/alt=["'][^"']+["']/i.test(i)}).length;
    add(noalt===0,imgs.length?(noalt?noalt+' image(s) in the content have no alt text':'All content images have alt text'):'No images in content (add at least one with alt text)',!imgs.length);
    if(k){
      add(f.title.value.toLowerCase().indexOf(k)>-1||f.mt.value.toLowerCase().indexOf(k)>-1,'Keyword is in the title');
      add((f.md.value||f.ex.value).toLowerCase().indexOf(k)>-1,'Keyword is in the description');
      add(f.slug.value.indexOf(k.replace(/\s+/g,'-'))>-1,'Keyword is in the URL slug',true);
      add(body.toLowerCase().slice(0,400).indexOf(k)>-1,'Keyword is in the first paragraph',true);
    } else add(false,'Add a focus keyword to get keyword checks',true);
    $('seo_checks').innerHTML=L.join('');
  }
  ['input','change'].forEach(function(ev){[f.title,f.slug,f.mt,f.md,f.kw,f.ex,f.ct].forEach(function(el){el&&el.addEventListener(ev,update)})});
  update();
})();
</script>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
