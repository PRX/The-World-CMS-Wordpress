export type Maybe<T> = T | null;

export type ApiAuthor = {
  name: string,
  id: Maybe<number>,
  image: Maybe<string>,
}

export type ApiEnclosure = {
  href: string,
  type: string,
  size: number,
  duration: number,
  status: string,
  episodeKey: string,
  audioKey: Maybe<string>,
  segment: Maybe<number>,
  version: string
}

export type ApiTaxonomy = {
  name: string,
  label: string,
  labels: {
    name: string,
    singular_name: string,
    search_items: string,
    popular_items: string,
    all_items: string,
    parent_item: string,
    parent_item_colon: string,
    name_field_description: string,
    slug_field_description: string,
    parent_field_description: null,
    desc_field_description: string,
    edit_item: string,
    view_item: string,
    update_item: string,
    add_new_item: string,
    new_item_name: string,
    separate_items_with_commas: string,
    add_or_remove_items: string,
    choose_from_most_used: string,
    not_found: string,
    no_terms: string,
    filter_by_item: null,
    items_list_navigation: string,
    items_list: string,
    most_used: string,
    back_to_items: string,
    item_link: string,
    item_link_description: string,
    menu_name: string,
    name_admin_bar: string,
    archives: string
  }
}

export type ApiTaxonomies = {
  [k: string]: ApiTaxonomy
}

export type ApiTerm = {
  id: number,
  name: string,
  taxonomy: ApiTaxonomy,
  count: number
}

export type ApiCategory = {
  name: string,
  existingTerms: ApiTerm[]
}

export type ApiAudio = {
  guid: string,
  databaseId: number,
  imported: boolean,
  editLink: string,
  datePublished: string,
  dateUpdated: string,
  url: string
}

export type ApiPost = {
  guid: string,
  databaseId: number,
  type: string,
  status: 'publish' | 'future' | 'draft' | 'pending' | 'private' | 'trash' | 'auto-draft' | 'inherit',
  imported: boolean,
  editLink: string,
  datePublished: string,
  dateUpdated: string,
}

export const ApiEpisodeTypes = ['episode', 'segment'] as const;
export type ApiEpisodeType = (typeof ApiEpisodeTypes)[number];

export type ApiEpisode = {
  existingPosts?: ApiPost[],
  existingPost: Maybe<ApiPost>,
  existingAudio: Maybe<ApiAudio>,
  wasImported: boolean,
  hasUpdatedAudio: boolean,
  type: ApiEpisodeType,
  id: string,
  guid: string,
  title: string,
  excerpt: Maybe<string>,
  content: string,
  datePublished: string,
  dateUpdated: Maybe<string>,
  dateBroadcast: Maybe<string>,
  dateKey: Maybe<string>,
  author: Maybe<ApiAuthor[]>
  enclosure: ApiEnclosure,
  categories: ApiCategory[]
}

export type ApiData = {
  date: Date,
  episodes: ApiEpisode[],
  segments: ApiEpisode[]
};

export type ApiEpisodeDeleteOptions = {
  deleteAudio: boolean,
  deleteParent: boolean
};
