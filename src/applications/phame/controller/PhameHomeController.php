<?php

final class PhameHomeController extends PhamePostController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $blogs = id(new PhameBlogQuery())
      ->setViewer($viewer)
      ->withStatuses(array(PhameBlog::STATUS_ACTIVE))
      ->needProfileImage(true)
      ->execute();

    $blog_phids = mpull($blogs, 'getPHID');

    $pager = id(new AphrontCursorPagerView())
      ->readFromRequest($request);

    $posts = id(new PhamePostQuery())
      ->setViewer($viewer)
      ->withBlogPHIDs($blog_phids)
      ->withVisibility(PhameConstants::VISIBILITY_PUBLISHED)
      ->executeWithCursorPager($pager);

    $actions = $this->renderActions($viewer);
    $action_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setText(pht('Actions'))
      ->setHref('#')
      ->setIconFont('fa-bars')
      ->addClass('phui-mobile-menu')
      ->setDropdownMenu($actions);

    $title = pht('Recent Posts');

    $header = id(new PHUIHeaderView())
      ->setHeader($title)
      ->addActionLink($action_button);

    $post_list = id(new PhamePostListView())
      ->setPosts($posts)
      ->setViewer($viewer)
      ->showBlog(true)
      ->setNodata(pht('No Recent Visible Posts.'));

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->setBorder(true);
    $crumbs->addTextCrumb(
      pht('Recent Posts'),
      $this->getApplicationURI('post/'));

    $page = id(new PHUIDocumentViewPro())
      ->setHeader($header)
      ->appendChild($post_list);

    $sidebar = id(new PhameBlogListView())
      ->setBlogs($blogs)
      ->setViewer($viewer);

    $phame_view = id(new PHUITwoColumnView())
      ->setMainColumn(array(
        $page,
      ))
      ->setSideColumn($sidebar)
      ->setDisplay(PHUITwoColumnView::DISPLAY_LEFT)
      ->addClass('phame-home-view');

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->appendChild(
        array(
          $phame_view,
      ));


  }

  private function renderActions($viewer) {
    $actions = id(new PhabricatorActionListView())
      ->setUser($viewer);

    $actions->addAction(
      id(new PhabricatorActionView())
        ->setIcon('fa-pencil')
        ->setHref($this->getApplicationURI('post/query/draft/'))
        ->setName(pht('My Drafts')));

    $actions->addAction(
      id(new PhabricatorActionView())
        ->setIcon('fa-pencil-square-o')
        ->setHref($this->getApplicationURI('post/'))
        ->setName(pht('All Posts')));

    return $actions;
  }

  private function renderBlogs($viewer, $blogs) {}

  protected function buildApplicationCrumbs() {
    $crumbs = parent::buildApplicationCrumbs();

    $can_create = $this->hasApplicationCapability(
      PhameBlogCreateCapability::CAPABILITY);

    $crumbs->addAction(
      id(new PHUIListItemView())
        ->setName(pht('New Blog'))
        ->setHref($this->getApplicationURI('/blog/new/'))
        ->setIcon('fa-plus-square')
        ->setDisabled(!$can_create)
        ->setWorkflow(!$can_create));

    return $crumbs;
  }

}
