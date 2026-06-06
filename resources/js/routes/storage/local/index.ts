import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'

export const upload = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: upload.url(args, options),
    method: 'put',
})

upload.definition = {
    methods: ["put"],
    url: '/storage/{path}',
} satisfies RouteDefinition<["put"]>

upload.url = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { path: args }
    }

    if (Array.isArray(args)) {
        args = {
            path: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        path: args.path,
    }

    return upload.definition.url
            .replace('{path}', parsedArgs.path.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

upload.put = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: upload.url(args, options),
    method: 'put',
})

const local = {
    upload: Object.assign(upload, upload),
}

export default local